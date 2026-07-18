<?php

namespace App\POS;

use App\Feedback\FeedbackAdmin;
use SilverStripe\ORM\DataObject;

/**
 * Class \App\POS\DonationCount
 *
 * @property float $Amount
 * @property ?string $CountDateTime
 * @property ?string $Source
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class DonationCount extends DataObject
{
    private static $db = [
        "Amount" => "Double",
        "CountDateTime" => "Datetime",
        "Source" => "Varchar(255)",
    ];

    private static $default_sort = "CountDateTime ASC";

    private static $field_labels = [
        "Total" => "Gesamtspenden",
        "CountDateTime" => "Zeitpunkt der Zählung",
        "Source" => "Quelle",
    ];

    private static $summary_fields = [
        "FormattedDateTime" => "Zeitpunkt",
        "Source" => "Quelle",
        "FormattedAmount" => "Betrag",
    ];

    private static $table_name = "DonationCount";

    private static $singular_name = "Spendenzählung";
    private static $plural_name = "Spendenzählungen";

    private static $url_segment = "donationcount";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        return $fields;
    }

    public function CMSEditLink()
    {
        $admin = FeedbackAdmin::singleton();
        $urlClass = str_replace('\\', '-', self::class);
        return $admin->Link("/{$urlClass}/EditForm/field/{$urlClass}/item/{$this->ID}/edit");
    }

    public function getFormattedAmount()
    {
        if ($this->Amount == 0) {
            return "Leer...";
        }
        return $this->Amount . " €";
    }

    public function getFormattedDateTime()
    {
        return date("d.m.Y H:i", strtotime($this->CountDateTime));
    }
}
