<?php

namespace App\Feedback;

use App\Feedback\FeedbackAdmin;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;

/**
 * Class \App\Feedback\FeedbackEntry
 *
 * @property ?string $Day
 * @property float $Stars
 * @property ?string $Comment
 * @property ?string $PLZ
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class FeedbackEntry extends DataObject
{
    private static $db = [
        "Day" => "Date",
        "Stars" => "Double",
        "Comment" => "Text",
        "PLZ" => "Varchar(5)",
    ];

    private static $default_sort = "Created DESC, Day DESC";

    private static $field_labels = [
        "Day" => "Tag",
        "Stars" => "Sterne",
        "Comment" => "Kommentar",
        "PLZ" => "PLZ",
    ];

    private static $summary_fields = [
        "FormattedCreationDate" => "Erstellt",
        "FormattedVisitDate" => "Besuchstag",
        "Stars" => "Sterne",
        "PLZ" => "PLZ",
        "Comment" => "Kommentar",
    ];

    private static $table_name = "FeedbackEntry";

    private static $singular_name = "Feedback";
    private static $plural_name = "Feedbacks";

    private static $url_segment = "feedbackentry";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab("Root.Main", LiteralField::create("Created", "<p>Erstellt: " . $this->getFormattedCreationDate() . "</p>"));
        return $fields;
    }

    private static $inline_editable = true;

    public function CMSEditLink()
    {
        $admin = FeedbackAdmin::singleton();
        $urlClass = str_replace('\\', '-', self::class);
        return $admin->Link("/{$urlClass}/EditForm/field/{$urlClass}/item/{$this->ID}/edit");
    }

    public function getFormattedCreationDate()
    {
        return $this->dbObject("Created")->Format("dd.MM.YYYY HH:mm:ss");
    }

    public function getFormattedVisitDate()
    {
        return $this->dbObject("Day")->Format("dd.MM.YYYY");
    }

    public function getShortSummary()
    {
        return $this->Comment ? substr($this->Comment, 0, 20) . "..." : "";
    }
}
