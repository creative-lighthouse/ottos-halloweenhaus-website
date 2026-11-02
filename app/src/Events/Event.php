<?php

namespace App\Events;

use DateTime;
use Throwable;
use DateTimeZone;
use IntlDateFormatter;
use App\Events\Registration;
use App\Events\EventTimeSlot;
use App\Feedback\FeedbackPage;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Control\Director;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Control\Email\Email;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class \App\Events\Event
 *
 * @property ?string $Title
 * @property ?string $Place
 * @property ?string $PlaceLink
 * @property ?string $EventDate
 * @property ?string $StartTime
 * @property ?string $EndTime
 * @property ?string $Description
 * @property ?string $InfoForAttendees
 * @property int $SlotDuration
 * @property bool $Visible
 * @property bool $HideQueueInfo
 * @property ?string $FeedbackMailSent
 * @property int $FeedbackMailCount
 * @property ?string $PersonalDataDeleted
 * @property int $ImageID
 * @method Image Image()
 * @method DataList<EventTimeSlot> TimeSlots()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class Event extends DataObject
{
    private static $db = [
        "Title" => "Varchar(255)",
        "Place" => "Varchar(255)",
        "PlaceLink" => "Varchar(255)",
        "EventDate" => "Date",
        "StartTime" => "Time",
        "EndTime" => "Time",
        "Description" => "HTMLText",
        "InfoForAttendees" => "HTMLText",
        "SlotDuration" => "Int",
        "Visible" => "Boolean",
        "HideQueueInfo" => "Boolean",
        "FeedbackMailSent" => "Datetime",
        "FeedbackMailCount" => "Int",
        "PersonalDataDeleted" => "Datetime",
    ];

    private static $has_one = [
        "Image" => Image::class,
    ];

    private static $has_many = [
        "TimeSlots" => EventTimeSlot::class,
        "Registrations" => Registration::class,
    ];

    private static $owns = [
        "Image"
    ];

    private static $default_sort = "EventDate ASC, StartTime ASC";

    private static $field_labels = [
        "Title" => "Name",
        "Place" => "Ort",
        "DateFormatted" => "Datum",
        "StartTime" => "Startzeit",
        "Description" => "Beschreibung",
        "MaxAttendees" => "Maximale Teilnehmerzahl",
        "InfoForAttendees" => "Informationen für Teilnehmer",
        "Image" => "Bild",
        "SlotDuration" => "Dauer eines Slots (in Minuten)",
        "HideQueueInfo" => "Hinweis zur regulären Warteschlange ausblenden",
    ];

    private static $summary_fields = [
        "Title" => "Name",
        "StartTime" => "Startzeit",
        "DateFormatted" => "Datum",
        "RegistrationsFormatted" => "Anmeldungen",
    ];

    private static $searchable_fields = [
        "Title",
        "Description",
    ];

    private static $table_name = "Event";

    private static $singular_name = "Event";
    private static $plural_name = "Events";

    private static $url_segment = "event";

    private const FEEDBACK_DEFAULT_SUBJECT = "Danke für Deinen Besuch bei Ottos Halloweenhaus";

    private const FEEDBACK_DEFAULT_CONTENT = "<p>Hallo {Registration.Title},</p>"
        . "<p>vielen Dank, dass Du uns bei Ottos Halloweenhaus besucht hast. Wir hoffen, Du hattest eine schaurig-schöne Zeit und unsere Show hat Dir gefallen.</p>"
        . "<p>Damit wir unsere Show immer weiter verbessern können, würden wir uns sehr über Deine ehrliche Meinung freuen. "
        . "Egal ob Highlights, die Dir in Erinnerung geblieben sind oder Kritikpunkte, die wir verbessern sollten: "
        . "Über ein kurzes (oder gerne auch ausführliches) Feedback würden wir uns sehr freuen.</p>";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName(["FeedbackMailSent", "FeedbackMailCount", "PersonalDataDeleted"]);

        if ($this->isInDB()) {
            $fields->addFieldToTab("Root.Main", LiteralField::create("FeedbackMailLink", $this->getFeedbackMailLinkHTML()));
        }

        return $fields;
    }

    private function getFeedbackMailLinkHTML(): string
    {
        // Can be run repeatedly (e.g. for late check-ins): every run only mails
        // registrations that haven't been mailed yet and still have an address.
        $link = Director::absoluteURL("dev/tasks/" . SendFeedbackMailTask::getNameWithoutNamespace()) . "?event=" . $this->ID;
        $html = sprintf(
            '<p><a href="%s" target="_blank" onclick="return confirm(\'Feedback-Mail an alle noch nicht angeschriebenen eingecheckten Registrierungen senden und anschließend die personenbezogenen Daten löschen?\')">'
            . 'Feedback-Mail senden &amp; personenbezogene Daten löschen</a> (%d neue Adresse(n))</p>',
            Convert::raw2att($link),
            count($this->getFeedbackMailRecipients())
        );

        if ($this->PersonalDataDeleted) {
            $html .= sprintf(
                '<p>Bisher an %d Adresse(n) versendet, personenbezogene Daten zuletzt am %s gelöscht.</p>',
                $this->FeedbackMailCount,
                $this->dbObject("PersonalDataDeleted")->Format("dd.MM.yyyy HH:mm")
            );
        }

        return $html;
    }

    /**
     * Unique (case-insensitive) addresses of checked-in registrations that
     * haven't received the thank-you mail yet.
     *
     * @return array<string, array{name: string, ids: int[], events: Event[]}>
     */
    public function getFeedbackMailRecipients(): array
    {
        return self::collectFeedbackMailRecipients([$this->ID]);
    }

    /**
     * Same as getFeedbackMailRecipients(), but across several events, so a
     * guest who visited more than one of them is still only listed once.
     * Each address is mapped to its registration IDs, the visited events and
     * the name to address them by (taken from the earliest registration).
     *
     * @param int[] $eventIDs
     * @return array<string, array{name: string, ids: int[], events: Event[]}>
     */
    public static function collectFeedbackMailRecipients(array $eventIDs): array
    {
        $recipients = [];
        $registrations = Registration::get()->filter([
            "EventID" => $eventIDs ?: [0],
            "Status" => "CheckedIn",
            "FeedbackEmailSent" => null,
        ])->sort("ID", "ASC");
        foreach ($registrations as $registration) {
            $address = strtolower(trim((string) $registration->Email));
            if ($address === "" || $address === "test@test.de" || !filter_var($address, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $recipients[$address] ??= ["name" => trim((string) $registration->Title), "ids" => [], "events" => []];
            $recipients[$address]["ids"][] = $registration->ID;
            $recipients[$address]["events"][$registration->EventID] ??= $registration->Event();
        }
        return $recipients;
    }

    /**
     * Sends the thank-you mail for the given events and, if every mail went
     * out, deletes the personal data of all their registrations afterwards.
     *
     * @param Event[] $events
     * @return array{sent: int, failed: string[]}
     */
    public static function runFeedbackMail(iterable $events): array
    {
        $events = is_array($events) ? $events : iterator_to_array($events);
        $result = self::sendFeedbackMails($events);
        if (!$result["failed"]) {
            foreach ($events as $event) {
                $event->deletePersonalData();
            }
        }
        return $result;
    }

    /**
     * Sends the thank-you mail (linking to the feedback page) once per address
     * across all given events. Registrations are flagged individually, so a
     * re-run after a partial failure only retries the addresses that didn't go out.
     *
     * {Event.Title} / {Event.DateFormatted} list all events the recipient
     * visited, e.g. "Halloween 1 und Halloween 2".
     *
     * @param Event[] $events
     * @return array{sent: int, failed: string[]}
     */
    public static function sendFeedbackMails(array $events): array
    {
        $siteConfig = SiteConfig::current_site_config();
        $feedbackLink = self::singleton()->getFeedbackLink();
        $subjectTemplate = (string) $siteConfig->FeedbackMessageSubject ?: self::FEEDBACK_DEFAULT_SUBJECT;
        $contentTemplate = (string) $siteConfig->FeedbackMessageContent ?: self::FEEDBACK_DEFAULT_CONTENT;

        $sent = 0;
        $failed = [];
        $sentPerEvent = [];
        $recipients = self::collectFeedbackMailRecipients(array_map(fn (Event $event) => $event->ID, $events));
        foreach ($recipients as $address => $recipient) {
            $vars = [
                "{Registration.Title}" => $recipient["name"],
                "{Registration.Name}" => $recipient["name"],
                "{Event.Title}" => self::joinList(array_map(fn (Event $event) => (string) $event->Title, $recipient["events"])),
                "{Event.DateFormatted}" => self::joinList(array_map(fn (Event $event) => (string) $event->DateFormatted, $recipient["events"])),
            ];
            $subject = strtr($subjectTemplate, $vars + ["{FeedbackLink}" => $feedbackLink]);
            // Content is HTML, so the (user-entered) values have to be escaped there
            $content = strtr($contentTemplate, array_map(fn ($value) => Convert::raw2xml($value), $vars) + ["{FeedbackLink}" => $feedbackLink]);

            try {
                $email = Email::create();
                $email->setTo($address);
                EmailNotification::applySender($email);
                $email->setSubject($subject);
                EmailNotification::applyLayout($email, [
                    "Subject" => $subject,
                    "Text" => DBField::create_field("HTMLText", $content),
                    "ButtonLink" => $feedbackLink,
                    "ButtonText" => "Jetzt Feedback geben",
                    "Signature" => "Dein Team von Ottos Halloweenhaus",
                ]);
                $email->send();
            } catch (Throwable $e) {
                $failed[] = $address;
                continue;
            }

            // Plain SQL instead of write(): Registration::onAfterWrite() would
            // otherwise re-trigger its confirmation/ticket mail logic.
            SQLUpdate::create('"Registration"', ['"FeedbackEmailSent"' => date("Y-m-d H:i:s")])
                ->addWhere(['"ID" IN (' . implode(",", array_map("intval", $recipient["ids"])) . ')'])
                ->execute();
            $sent++;
            foreach (array_keys($recipient["events"]) as $eventID) {
                $sentPerEvent[$eventID] = ($sentPerEvent[$eventID] ?? 0) + 1;
            }
        }

        foreach ($events as $event) {
            $event->FeedbackMailSent = date("Y-m-d H:i:s");
            $event->FeedbackMailCount += $sentPerEvent[$event->ID] ?? 0;
            $event->write();
        }

        return ["sent" => $sent, "failed" => $failed];
    }

    /**
     * "A", "A und B", "A, B und C"
     *
     * @param string[] $items
     */
    private static function joinList(array $items): string
    {
        $items = array_values(array_unique($items));
        $last = array_pop($items);
        return $items ? implode(", ", $items) . " und " . $last : (string) $last;
    }

    /**
     * Removes name and e-mail address from all registrations of this event
     * (the registrations themselves stay for statistics) and drops the stored
     * notification mails, which contain the same data.
     */
    public function deletePersonalData(): void
    {
        // Plain SQL for the same reason as in sendFeedbackMails().
        SQLUpdate::create('"Registration"', ['"Title"' => null, '"Email"' => null])
            ->addWhere(['"EventID"' => $this->ID])
            ->execute();

        // delete() instead of an update: EmailNotification sends on every write.
        $notifications = EmailNotification::get()->filterAny([
            "EventID" => $this->ID,
            "RegistrationID" => $this->getRegistrations()->column("ID") ?: [0],
        ]);
        foreach ($notifications as $notification) {
            $notification->delete();
        }

        $this->PersonalDataDeleted = date("Y-m-d H:i:s");
        $this->write();
    }

    public function getFeedbackLink(): string
    {
        $feedbackPage = Versioned::get_by_stage(FeedbackPage::class, Versioned::LIVE)->first();
        return $feedbackPage ? $feedbackPage->AbsoluteLink() : Director::absoluteURL("feedback");
    }

    function InFuture()
    {
        return $this->dbObject("StartDate")->InFuture();
    }

    function getRegistrationsFormatted()
    {
        if ($this->MaxAttendees) {
            $registrations = Registration::get()->filter("EventID", $this->ID);
            return sprintf("%d / %d", $registrations->Count(), $this->MaxAttendees) . " Anmeldungen";
        } else {
            return $this->getRegistrations()->Count() . " Anmeldungen";
        }
    }

    function getRegistrations()
    {
        $registrations = Registration::get()->filter("EventID", $this->ID);
        return $registrations;
    }

    function getRemainingSeats()
    {
        $registrations = Registration::get()->filter("EventID", $this->ID);
        if ($this->MaxAttendees) {
            return $this->MaxAttendees - $registrations->Count();
        } else {
            return 0;
        }
    }

    public function getRegistrationLink()
    {
        $holderNew = Versioned::get_by_stage(EventPage::class, Versioned::LIVE)->sort("ID", "ASC")->first();
        if ($holderNew) {
            return $holderNew->AbsoluteLink("register/") . $this->ID;
        }
        return "/404";
    }

    public function getLink()
    {
        $holderNew = Versioned::get_by_stage(EventPage::class, Versioned::LIVE)->sort("ID", "ASC")->first();
        if ($holderNew) {
            return $holderNew->AbsoluteLink();
        }
        return "/404";
    }

    public function getDateFormatted()
    {
        if (!$this->EventDate) {
            return "–";
        }

        return date("d.m.y", strtotime($this->EventDate));
    }

    public function getDateWeekday()
    {
        $fmt = new IntlDateFormatter('de', IntlDateFormatter::FULL, IntlDateFormatter::NONE, null, null, "ccc");
        $timestamp = strtotime($this->EventDate);

        return $fmt->format($timestamp);
    }

    public function getDateDay()
    {
        $fmt = new IntlDateFormatter('de', IntlDateFormatter::FULL, IntlDateFormatter::NONE, null, null, "d");
        $timestamp = strtotime($this->EventDate);

        return $fmt->format($timestamp);
    }

    public function getDateMonthTitle()
    {
        $fmt = new IntlDateFormatter('de', IntlDateFormatter::FULL, IntlDateFormatter::NONE, null, null, "MMM");
        $timestamp = strtotime($this->EventDate);

        return $fmt->format($timestamp);
    }

    public function FreeTimeSlots()
    {
        $now = new DateTime("", new DateTimeZone("Europe/Berlin"));
        $timeslots = $this->TimeSlots()->filter([
            "SlotTime:GreaterThanOrEqual" => $now->format("H:i:s"),
            "Active" => true
        ]);
        $timeslotsWithSpace = ArrayList::create();
        foreach ($timeslots as $timeslot) {
            if ($timeslot->getFreeSlotCount() > 0) {
                $timeslotsWithSpace->push($timeslot);
            }
        }
        return $timeslotsWithSpace;
    }

    public function FreeCouponTimeSlots()
    {
        $now = new DateTime("", new DateTimeZone("Europe/Berlin"));
        $timeslots = $this->TimeSlots()->filter([
            "SlotTime:GreaterThanOrEqual" => $now->format("H:i:s"),
            "Active" => true,
        ]);
        $timeslotsWithSpace = ArrayList::create();
        foreach ($timeslots as $timeslot) {
            if ($timeslot->getFreeCouponSlotCount() > 0) {
                $timeslotsWithSpace->push($timeslot);
            }
        }
        return $timeslotsWithSpace;
    }

    public function FreeTimeSlotsInFuture()
    {
        $now = new DateTime("", new DateTimeZone("Europe/Berlin"));
        if ($this->EventDate > $now->format("Y-m-d")) {
            $timeslots = $this->TimeSlots()->filter([
                "Active" => true
            ]);
            $timeslotsWithSpace = ArrayList::create();
            foreach ($timeslots as $timeslot) {
                if ($timeslot->getFreeSlotCount() > 0) {
                    $timeslotsWithSpace->push($timeslot);
                }
            }
            return $timeslotsWithSpace;
        } else {
            return $this->FreeTimeSlots();
        }
    }

    public function FreeCouponTimeSlotsInFuture()
    {
        $now = new DateTime("", new DateTimeZone("Europe/Berlin"));
        if ($this->EventDate > $now->format("Y-m-d")) {
            $timeslots = $this->TimeSlots()->filter([
                "Active" => true
            ]);
            $timeslotsWithSpace = ArrayList::create();
            foreach ($timeslots as $timeslot) {
                if ($timeslot->getFreeCouponSlotCount() > 0) {
                    $timeslotsWithSpace->push($timeslot);
                }
            }
            return $timeslotsWithSpace;
        } else {
            return $this->FreeCouponTimeSlots();
        }
    }

    public function FullTimeSlots()
    {
        $now = new DateTime("", new DateTimeZone("Europe/Berlin"));
        $timeslots = $this->TimeSlots()->filter([
            "Active" => true
        ]);
        $timeslotsWithoutSpace = ArrayList::create();
        foreach ($timeslots as $timeslot) {
            if ($timeslot->getFreeSlotCount() <= 0) {
                $timeslotsWithoutSpace->push($timeslot);
            }
        }
        return $timeslotsWithoutSpace;
    }

    public function FullCouponTimeSlots()
    {
        $now = new DateTime("", new DateTimeZone("Europe/Berlin"));
        $timeslots = $this->TimeSlots()->filter(array(
            "Active" => true
        ));
        $timeslotsWithoutSpace = ArrayList::create();
        foreach ($timeslots as $timeslot) {
            if ($timeslot->getFreeCouponSlotCount() <= 0) {
                $timeslotsWithoutSpace->push($timeslot);
            }
        }
        return $timeslotsWithoutSpace;
    }
}
