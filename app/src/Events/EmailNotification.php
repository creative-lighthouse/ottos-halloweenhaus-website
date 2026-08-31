<?php

namespace App\Events;

use SilverStripe\Assets\File;
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
            $adminEmail = SiteConfig::current_site_config()->EventAdminEmail;

            $email = Email::create();
            $email->setTo($this->Email);

            // The "From" address must stay on a domain we DKIM/SPF-sign
            // (SS_ADMIN_EMAIL), otherwise Gmail & co. reject the mail. The
            // organiser address from the CMS is only used as Reply-To so
            // attendee replies still reach the team.
            $fromAddress = Environment::getEnv('SS_ADMIN_EMAIL') ?: Email::config()->get('admin_email');
            if ($fromAddress) {
                $email->setFrom($fromAddress);
            }
            if ($adminEmail) {
                $email->setReplyTo($adminEmail);
            }

            $email->html($this->Text);
            $email->text($this->Text);
            //if ($this->Attachment()) {
            //    $email->addAttachment($this->Attachment());
            //}
            $email->setHTMLTemplate('emails/EventEmail');
            $email->setPlainTemplate('emails/EventEmailPlain');
            $email->setSubject($this->Title);
            $email->setData([
                "Registration" => $registration,
                "Subject" => $this->Title,
                "Text" => DBField::create_field('HTMLText', $this->Text)
            ]);
            $email->send();
        }
    }

    function getDateFormatted()
    {
        if ($this->Created) {
            return $this->dbObject("Created")->Format("dd.MM.yyyy HH:mm");
        }
    }
}
