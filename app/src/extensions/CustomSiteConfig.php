<?php

namespace App\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\TextField;

/**
 * Class \App\CustomSiteConfig
 *
 * @property SiteConfig|CustomSiteConfig $owner
 * @property ?string $DateText
 * @property ?string $PlaceText
 * @property bool $ShowBanner
 * @property ?string $BannerText
 * @property ?string $AckMessageSubject
 * @property ?string $AckMessageContent
 * @property ?string $TicketMessageSubject
 * @property ?string $TicketMessageContent
 * @property ?string $NewRegisterMessageSubject
 * @property ?string $NewRegisterMessageContent
 * @property ?string $FeedbackMessageSubject
 * @property ?string $FeedbackMessageContent
 * @property bool $EmailsActive
 * @property ?string $EventAdminEmail
 * @property int $MaxGroupSize
 * @property ?string $SocialTikTok
 * @property ?string $SocialInstagram
 * @property ?string $SocialFacebook
 * @property ?string $SocialLinkedIn
 * @property ?string $SocialYoutube
 */
class CustomSiteConfig extends Extension
{

    private static $db = [
        'DateText' => 'Varchar(255)',
        "PlaceText" => "Varchar(255)",
        "ShowBanner" => "Boolean",
        "BannerText" => "Varchar(511)",
        'AckMessageSubject' => 'Varchar(255)',
        'AckMessageContent' => 'HTMLText',
        'TicketMessageSubject' => 'Varchar(255)',
        'TicketMessageContent' => 'HTMLText',
        'NewRegisterMessageSubject' => 'Varchar(255)',
        'NewRegisterMessageContent' => 'HTMLText',
        'FeedbackMessageSubject' => 'Varchar(255)',
        'FeedbackMessageContent' => 'HTMLText',
        'EmailsActive' => 'Boolean',
        'EventAdminEmail' => 'Varchar(255)',
        "MaxGroupSize" => "Int",
        "SocialTikTok" => "Varchar(255)",
        "SocialInstagram" => "Varchar(255)",
        "SocialFacebook" => "Varchar(255)",
        "SocialLinkedIn" => "Varchar(255)",
        "SocialYoutube" => "Varchar(255)",
    ];

    private static $defaults = [
        'EmailsActive' => true,
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab("Root.Main", TextField::create("DateText", "Date Text"));
        $fields->addFieldToTab("Root.Main", TextField::create("PlaceText", "Place Text"));
        $fields->addFieldToTab("Root.Main", CheckboxField::create("ShowBanner", "Show Banner"));
        $fields->addFieldToTab("Root.Main", TextField::create("BannerText", "Banner Text"));
        $fields->addFieldToTab('Root.Social Media', TextField::create('SocialTikTok', 'TikTok'));
        $fields->addFieldToTab('Root.Social Media', TextField::create('SocialInstagram', 'Instagram'));
        $fields->addFieldToTab('Root.Social Media', TextField::create('SocialFacebook', 'Facebook'));
        $fields->addFieldToTab('Root.Social Media', TextField::create('SocialLinkedIn', 'LinkedIn'));
        $fields->addFieldToTab('Root.Social Media', TextField::create('SocialYoutube', 'YouTube'));

    }

    /**
     * Edited in the Events admin (see App\Events\EventAdmin), not in the Settings.
     */
    public function getEventSettingsFields(): FieldList
    {
        return FieldList::create(
            TextField::create("MaxGroupSize", "Maximale Gruppengröße zum registrieren"),
            CheckboxField::create('EmailsActive', 'E-Mails aktiviert'),
            TextField::create('EventAdminEmail', 'Event Admin Emailadresse'),
            TextField::create('AckMessageSubject', 'Empfangsbestätigung Betreff'),
            HTMLEditorField::create('AckMessageContent', 'Empfangsbestätigung Inhalt'),
            TextField::create('TicketMessageSubject', 'Ticket-Email Betreff'),
            HTMLEditorField::create('TicketMessageContent', 'Ticket-Email Inhalt'),
            TextField::create('NewRegisterMessageSubject', 'Neue Anmeldung Betreff'),
            HTMLEditorField::create('NewRegisterMessageContent', 'Neue Anmeldung Inhalt'),
            TextField::create('FeedbackMessageSubject', 'Danke-/Feedback-Email Betreff')
                ->setDescription('Leer lassen für Standardtext. Platzhalter: {Registration.Title}, {Event.Title}, {Event.DateFormatted}'),
            HTMLEditorField::create('FeedbackMessageContent', 'Danke-/Feedback-Email Inhalt')
                ->setDescription('Leer lassen für Standardtext. Platzhalter: {Registration.Title}, {Event.Title}, {Event.DateFormatted}, {FeedbackLink}. Bei mehreren Registrierungen mit derselben E-Mail-Adresse wird der Name der ersten verwendet. Button zur Feedback-Seite und Grußformel werden automatisch ergänzt.')
        );
    }

    public function getMaxGroupSizeAsArraySize()
    {
        //Create an array in the size of MaxGroupSize
        $sizes = [];
        for ($i = 1; $i <= $this->owner->MaxGroupSize; $i++) {
            $sizes[] = $i;
        }
        return $sizes;
    }
}
