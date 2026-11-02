<?php

namespace App\Events;

use SilverStripe\Assets\File;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Environment;
use SilverStripe\Control\Email\Email;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class \App\Events\EmailNotification
 *
 * @property ?string $Title
 * @property ?string $Text
 * @property ?string $Email
 * @property ?string $Type
 * @property int $EventID
 * @property int $RegistrationID
 * @property int $AttachmentID
 * @method Event Event()
 * @method Registration Registration()
 * @method File Attachment()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class EmailNotification extends DataObject
{

    private static $db = array(
        "Title" => "Varchar(512)",
        "Text" => "Text",
        "Email" => "Varchar(255)",
        "Type" => "Varchar(255)",
    );
    private static $has_one = array(
        "Event" => Event::class,
        "Registration" => Registration::class,
        "Attachment" => File::class,
    );

    private static $summary_fields = array(
        "Event.Title" => "Veranstaltung",
        "Registration.Title" => "Empfänger",
        "Email" => "Email-Adresse",
        "Created" => "Datum",
        "Title" => "Betreff",
    );

    private static $default_sort = "Created DESC";

    private static $field_labels = array(
        "Title" => "Betreff",
        "Text" => "Text",
        "Email" => "Email-Adresse",
        "Event" => "Veranstaltung",
    );

    private static $table_name = "email_notification";

    private static $singular_name = "Email Benachrichtigung";
    private static $plural_name = "Email Benachrichtigungen";

    private static $searchable_fields = array(
        "Title",
        "Event.Title",
        "Email",
        "Registration.Title",
    );

    function getCMSFields()
    {
        $fields = parent::getCMSFields();

        return $fields;
    }

    function onBeforeWrite()
    {
        parent::onBeforeWrite();
    }

    function onAfterWrite()
    {
        parent::onAfterWrite();

        $registration = $this->Registration();
        $this->Email = strtolower((string) $this->Email);

        // Nothing to send without a valid recipient (e.g. the admin notification
        // when SiteConfig.EventAdminEmail is not configured). Bailing out keeps
        // the surrounding registration transaction from blowing up.
        if ($this->Email !== "" && $this->Email !== "test@test.de" && filter_var($this->Email, FILTER_VALIDATE_EMAIL)) {
            $email = Email::create();
            $email->setTo($this->Email);
            self::applySender($email);

            //if ($this->Attachment()) {
            //    $email->addAttachment($this->Attachment());
            //}
            $email->setSubject($this->Title);
            self::applyLayout($email, [
                "Registration" => $registration,
                "Subject" => $this->Title,
                "Text" => DBField::create_field('HTMLText', $this->Text)
            ]);
            $email->send();
        }
    }

    /**
     * The "From" address must stay on a domain we DKIM/SPF-sign
     * (SS_ADMIN_EMAIL), otherwise Gmail & co. reject the mail. The
     * organiser address from the CMS is only used as Reply-To so
     * attendee replies still reach the team.
     * Always set From explicitly (with a display name) rather than leaving
     * it to Silverstripe's own no-reply@<host> fallback, which carries no
     * name and is what GMX/Web.de tend to flag as spam.
     */
    public static function applySender(Email $email): void
    {
        $fromAddress = Environment::getEnv('SS_ADMIN_EMAIL')
            ?: Email::config()->get('admin_email')
            ?: 'no-reply@' . parse_url(Director::absoluteBaseURL(), PHP_URL_HOST);
        $email->setFrom($fromAddress, "Ottos Halloweenhaus");

        $adminEmail = SiteConfig::current_site_config()->EventAdminEmail;
        if ($adminEmail) {
            $email->setReplyTo($adminEmail);
        }
    }

    /**
     * Renders the mail in the shared Ottos Halloweenhaus layout (see
     * templates/emails/BrandedEmail.ss). $data needs "Subject" and "Text",
     * optionally "ButtonLink"/"ButtonText" and "Signature".
     *
     * Images are embedded (cid:) rather than linked: linked images depend on
     * the request host (wrong when run via sake) and are blocked by many mail
     * clients until the reader allows remote content.
     */
    public static function applyLayout(Email $email, array $data): void
    {
        $siteConfig = SiteConfig::current_site_config();
        $images = array_filter([
            "logo" => true,
            "website" => true,
            "instagram" => (bool) $siteConfig->SocialInstagram,
            "youtube" => (bool) $siteConfig->SocialYoutube,
        ]);
        // Only embed what the template shows, unused ones would appear as attachments
        foreach (array_keys($images) as $name) {
            $email->embedFromPath(Director::baseFolder() . "/app/client/images/email/" . $name . ".png", $name, "image/png");
        }

        $email->setHTMLTemplate('emails/BrandedEmail');
        $email->setPlainTemplate('emails/BrandedEmailPlain');
        $email->setData($data + [
            "WebsiteLink" => Director::absoluteBaseURL(),
            "InstagramLink" => $siteConfig->SocialInstagram,
            "YoutubeLink" => $siteConfig->SocialYoutube,
            "LogoURL" => "cid:logo",
            "InstagramIconURL" => "cid:instagram",
            "YoutubeIconURL" => "cid:youtube",
            "WebsiteIconURL" => "cid:website",
        ]);
    }

    function getDateFormatted()
    {
        if ($this->Created) {
            return $this->dbObject("Created")->Format("dd.MM.yyyy HH:mm");
        }
    }
}
