<?php

namespace App\Events;

use DateTime;
use Firebase\JWT\JWT;
use App\Events\Event;
use App\Events\EventAdmin;
use App\Events\EventTimeSlot;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Environment;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ReadonlyField;
use Endroid\QrCode\RoundBlockSizeMode;
use SilverStripe\SiteConfig\SiteConfig;
use Endroid\QrCode\ErrorCorrectionLevel;

/**
 * Class \App\Events\Registration
 *
 * @property ?string $Title
 * @property ?string $Email
 * @property int $GroupSize
 * @property ?string $Hash
 * @property ?string $ConfirmEmailSent
 * @property ?string $TicketEmailSent
 * @property ?string $Status
 * @property ?string $Type
 * @property ?string $ZIP
 * @property int $EventID
 * @property int $TimeSlotID
 * @property int $UsedCouponID
 * @method Event Event()
 * @method EventTimeSlot TimeSlot()
 * @method EventCoupon UsedCoupon()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class Registration extends DataObject
{
    private static $db = [
        "Title" => "Varchar(255)",
        "Email" => "Varchar(255)",
        "GroupSize" => "Int",
        "Hash" => "Varchar(255)",
        "ConfirmEmailSent" => "Datetime",
        "TicketEmailSent" => "Datetime",
        "Status" => "Varchar(255)",
        "Type" => "Varchar(255)",
        "ZIP" => "Varchar(5)",
        "ConfirmSecurityID" => "Varchar(3)",
        "CheckInCode" => "Varchar(8)",
    ];

    private static $has_one = [
        "Event" => Event::class,
        "TimeSlot" => EventTimeSlot::class,
        "UsedCoupon" => EventCoupon::class,
    ];

    private static $default_sort = "Created DESC";

    private static $field_labels = [
        "Title" => "Name",
        "Email" => "E-Mail",
        "Event" => "Event",
        "TimeSlot" => "Zeitslot",
        "Created" => "Datum",
        "Status" => "Status",
        "GroupSize" => "Gruppengröße",
        "ZIP" => "PLZ",
        "CheckInCode" => "Check-In-Code",
    ];

    private static $summary_fields = [
        "StatusText" => "Status",
        "Title" => "Name",
        "Email" => "E-Mail",
        "Event.DateFormatted" => "Datum",
        "TimeSlot.SlotTimeFormatted" => "Slotzeit",
        "GroupSize" => "Personen",
    ];

    private static $searchable_fields = [
        "Title",
        "Email",
        "CheckInCode",
    ];

    private static $table_name = "Registration";

    private static $singular_name = "Registrierung";
    private static $plural_name = "Registrierungen";

    private static $url_segment = "registrations";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab(
            "Root.Main",
            array(
                DropdownField::create("Status", "Status", [
                    "Registered" => "Registered",
                    "Confirmed" => "Confirmed",
                    "CheckedIn" => "CheckedIn",
                    "Cancelled" => "Cancelled",
                ])
            )
        );
        $fields->removeByName("Type");
        $fields->removeByName("ConfirmSecurityID");

        // Auto-generated in onBeforeWrite() - never hand-edited, since staff
        // read it back off the ticket to manually check a guest in.
        $fields->replaceField("CheckInCode", ReadonlyField::create("CheckInCode", "Check-In-Code"));

        return $fields;
    }

    public function CMSEditLink()
    {
        $admin = EventAdmin::singleton();
        $urlClass = str_replace('\\', '-', self::class);
        return $admin->Link("/{$urlClass}/EditForm/field/{$urlClass}/item/{$this->ID}/edit");
    }

    function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $now = new DateTime();
        $now = $now->format("Y-m-d H:i:s");

        if (!$this->Hash) {
            $this->Hash = substr(md5(string: $now . $this->Title . $this->Email), 0, 8);
        }

        if (!$this->ConfirmSecurityID) {
            $this->ConfirmSecurityID = str_pad((string) random_int(0, 999), 3, "0", STR_PAD_LEFT);
        }

        if (!$this->CheckInCode) {
            do {
                $code = self::generateCheckInCode();
            } while (self::get()->filter("CheckInCode", $code)->exists());
            $this->CheckInCode = $code;
        }
    }

    /**
     * 8 characters from a 32-symbol alphabet (digits + uppercase letters,
     * excluding 0/O/1/I which are easy to mix up when read off a screen in
     * bad lighting) - a manual fallback for checking a guest in when their
     * QR code can't be scanned.
     */
    private static function generateCheckInCode(): string
    {
        $alphabet = "23456789ABCDEFGHJKLMNPQRSTUVWXYZ";
        $code = "";
        for ($i = 0; $i < 8; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $code;
    }

    function onAfterWrite()
    {
        parent::onAfterWrite();

        if ($this->ConfirmEmailSent == null && SiteConfig::current_site_config()->EmailsActive) {
            $this->sendReceiveConfirmation();
        }
        if ($this->Status == "Confirmed" && $this->TicketEmailSent == null && SiteConfig::current_site_config()->EmailsActive) {
            $this->sendTicketEmail();
        }
    }

    public function sendReceiveConfirmation()
    {
        if ($this->Email != "test@test.de") {

            $eventpage = EventPage::get()->first();
            $confirmLink = $eventpage->AbsoluteLink("registrationconfirm?event=" . $this->EventID . "&hash=" . $this->Hash . "&securityid=" . $this->ConfirmSecurityID);

            // Variablen für Platzhalter
            $vars = [
                '{Registration.Title}' => (string) $this->Title,
                '{Registration.Name}' => (string) $this->Title,
                '{Registration.Email}' => (string) $this->Email,
                '{Registration.GroupSize}' => (string) $this->GroupSize,
                '{Registration.UnsubscribeLink}' => (string) $this->getUnsubscribeLink(),
                '{Event.Title}' => (string) ($this->Event ? $this->Event->Title : ''),
                '{Event.DateFormatted}' => (string) ($this->Event ? $this->Event->DateFormatted : ''),
                '{Event.Place}' => (string) ($this->Event ? $this->Event->Place : ''),
                '{TimeSlot.SlotTime}' => (string) ($this->TimeSlot ? $this->TimeSlot->SlotTime : ''),
                '{TimeSlot.SlotTimeFormatted}' => (string) $this->TimeSlot->SlotTimeFormatted,
                '{TimeSlot.SlotTimeEndFormatted}' => (string) $this->TimeSlot->SlotTimeEndFormatted,
                '{TimeSlot.FreeSlotCount}' => (string) $this->TimeSlot->getFreeSlotCount(),
                '{TimeSlot.MaxAttendees}' => (string) $this->TimeSlot->MaxAttendees,
                '{ConfirmLink}' => (string) $confirmLink
            ];

            //Send email to client
            $emailConfirmation = EmailNotification::create();
            $subject = (string) SiteConfig::current_site_config()->AckMessageSubject;
            $content = (string) SiteConfig::current_site_config()->AckMessageContent;
            foreach ($vars as $key => $value) {
                $subject = str_replace($key, $value, $subject);
                $content = str_replace($key, $value, $content);
            }
            $emailConfirmation->Title = $subject;
            $emailConfirmation->Text = $content;
            $emailConfirmation->Type = "AckMessage";
            $emailConfirmation->Email = $this->Email;
            $emailConfirmation->Event = $this->Event;
            $emailConfirmation->Registration = $this;
            $emailConfirmation->write();

            //Send email to admin
            $emailNotification = EmailNotification::create();
            $adminSubject = (string) SiteConfig::current_site_config()->NewRegisterMessageSubject;
            $adminContent = (string) SiteConfig::current_site_config()->NewRegisterMessageContent;
            foreach ($vars as $key => $value) {
                $adminSubject = str_replace($key, $value, $adminSubject);
                $adminContent = str_replace($key, $value, $adminContent);
            }
            $emailNotification->Title = $adminSubject;
            $emailNotification->Text = $adminContent;
            $emailNotification->Type = "NewRegistration";
            $emailNotification->Email = SiteConfig::current_site_config()->EventAdminEmail;
            $emailNotification->Event = $this->Event;
            $emailNotification->Registration = $this;
            $emailNotification->write();

            $now = date("Y-m-d H:i:s");
            $this->ConfirmEmailSent = date("Y-m-d H:i:s", strtotime($now));
            $this->write();
        }
    }

    public function sendTicketEmail()
    {
        if ($this->Email != "test@test.de") {
            $eventpage = EventPage::get()->first();
            $ticketLink = $eventpage->AbsoluteLink("ticket/" . $this->Hash);

            // Variablen für Platzhalter
            $vars = [
                '{Registration.Title}' => (string) $this->Title,
                '{Registration.Name}' => (string) $this->Title,
                '{Registration.Email}' => (string) $this->Email,
                '{Registration.GroupSize}' => (string) $this->GroupSize,
                '{Registration.UnsubscribeLink}' => (string) $this->getUnsubscribeLink(),
                '{Event.Title}' => (string) $this->Event->Title,
                '{Event.DateFormatted}' => (string) $this->Event->DateFormatted,
                '{Event.Place}' => (string) $this->Event->Place,
                '{TimeSlot.SlotTime}' => (string) $this->TimeSlot->SlotTime,
                '{TimeSlot.SlotTimeFormatted}' => (string) $this->TimeSlot->SlotTimeFormatted,
                '{TimeSlot.SlotTimeEndFormatted}' => (string) $this->TimeSlot->SlotTimeEndFormatted,
                '{TicketLink}' => (string) $ticketLink
            ];

            //Send email to client
            $emailConfirmation = EmailNotification::create();
            $subject = (string) SiteConfig::current_site_config()->TicketMessageSubject;
            $content = (string) SiteConfig::current_site_config()->TicketMessageContent;
            foreach ($vars as $key => $value) {
                $subject = str_replace($key, $value, $subject);
                $content = str_replace($key, $value, $content);
            }
            $emailConfirmation->Title = $subject;
            $emailConfirmation->Text = $content;
            $emailConfirmation->Type = "AckMessage";
            $emailConfirmation->Email = $this->Email;
            $emailConfirmation->Event = $this->Event;
            $emailConfirmation->Registration = $this;
            $emailConfirmation->write();

            $now = date("Y-m-d H:i:s");
            $this->TicketEmailSent = date("Y-m-d H:i:s", strtotime($now));
            $this->write();
        }
    }

    public function getTicketLink()
    {
        $holder = EventPage::get()->sort("ID", "ASC")->First();
        if ($holder) {
            return $holder->AbsoluteLink("ticket") . "/" . $this->Hash;
        }
        return "/404";
    }

    public function getUnsubscribeLink()
    {
        $holder = EventPage::get()->sort("ID", "ASC")->First();
        if ($holder) {
            return $holder->AbsoluteLink("unsubscribe") . "/" . $this->Hash;
        }
        return "/404";
    }

    public function getValidateLink()
    {
        $holder = EventPage::get()->sort("ID", "ASC")->First();
        if ($holder) {
            return $holder->AbsoluteLink("ticket") . "/" . $this->Hash;
        }
        return "/404";
    }

    public function getQRCode()
    {
        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $this->getValidateLink(),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );
        $qrCode = $builder->build();
        return $qrCode->getDataUri();
    }

    /**
     * Builds a "Save to Google Wallet" link. The pass class + object are embedded
     * directly in the signed JWT, so no prior REST API call is needed to create
     * them - Google creates/updates both from the JWT payload on save.
     */
    public function getGoogleWalletLink()
    {
        $issuerId = Environment::getEnv('GOOGLE_WALLET_ISSUER_ID');
        $keyPath = Environment::getEnv('GOOGLE_WALLET_SERVICE_ACCOUNT_KEY_PATH');
        if (!$issuerId || !$keyPath) {
            return null;
        }

        $absoluteKeyPath = Director::is_absolute($keyPath) ? $keyPath : Director::baseFolder() . '/' . $keyPath;
        if (!file_exists($absoluteKeyPath)) {
            return null;
        }
        $serviceAccount = json_decode(file_get_contents($absoluteKeyPath), true);

        $classId = $issuerId . '.ottos_halloweenhaus_ticket';
        $objectId = $issuerId . '.registration_' . $this->Hash;

        $genericClass = [
            "id" => $classId,
        ];

        $genericObject = [
            "id" => $objectId,
            "classId" => $classId,
            "state" => $this->Status === "Cancelled" ? "INACTIVE" : "ACTIVE",
            "logo" => [
                "sourceUri" => ["uri" => "https://ottos-halloweenhaus.de/_resources/app/client/images/ohh_logo2026_profile_white.png"],
            ],
            "cardTitle" => ["defaultValue" => ["language" => "de", "value" => $this->Event ? $this->Event->Title : "Ottos Halloweenhaus"]],
            "header" => ["defaultValue" => ["language" => "de", "value" => $this->Title]],
            "subheader" => ["defaultValue" => ["language" => "de", "value" => $this->GroupSize . " Person(en)"]],
            "hexBackgroundColor" => "#151515",
            "textModulesData" => [
                [
                    "id" => "event_datetime",
                    "header" => "Termin",
                    "body" => trim(
                        ($this->Event ? $this->Event->DateFormatted : "") . " · " .
                        ($this->TimeSlot ? $this->TimeSlot->SlotTimeFormatted . " - " . $this->TimeSlot->SlotTimeEndFormatted . " Uhr" : "")
                    ),
                ],
                [
                    "id" => "event_place",
                    "header" => "Ort",
                    "body" => (string) ($this->Event ? $this->Event->Place : ""),
                ],
            ],
            "barcode" => [
                "type" => "QR_CODE",
                "value" => $this->getValidateLink(),
                "alternateText" => $this->Hash,
            ],
        ];

        $payload = [
            "iss" => $serviceAccount["client_email"],
            "aud" => "google",
            "typ" => "savetowallet",
            "iat" => time(),
            "payload" => [
                "genericClasses" => [$genericClass],
                "genericObjects" => [$genericObject],
            ],
        ];

        $jwt = JWT::encode($payload, $serviceAccount["private_key"], "RS256");
        return "https://pay.google.com/gp/v/save/" . $jwt;
    }

    public function getStatusText()
    {
        switch ($this->Status) {
            case "Registered":
                return "Registriert";
            case "Confirmed":
                return "Bestätigt";
            case "CheckedIn":
                return "Eingecheckt";
            case "Cancelled":
                return "Gelöscht";
        }
    }
}
