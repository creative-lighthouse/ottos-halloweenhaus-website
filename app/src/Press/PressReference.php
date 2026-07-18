<?php

namespace App\Press;

use SilverStripe\Security\Permission;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\DataObject;

/**
 * Class \App\Press\PressReference
 *
 * @property ?string $Title
 * @property ?string $Text
 * @property int $SortField
 * @property bool $OnSlider
 * @property ?string $PublishDate
 * @property int $SourceLinkID
 * @method Link SourceLink()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class PressReference extends DataObject
{
    private static $db = [
        "Title" => "Varchar(255)",
        "Text" => "HTMLText",
        "SortField" => "Int",
        "OnSlider" => "Boolean",
        "PublishDate" => "Date",
    ];

    private static $has_one = [
        "SourceLink" => Link::class,
    ];

    private static $owns = [
        "SourceLink",
    ];

    private static $field_labels = [
        "Title" => "Quelle",
        "Text" => "Text",
        "OnSlider" => "Auf Slider anzeigen",
        "SourceLink" => "Quellen-Link",
        "PublishDate" => "Veröffentlichungsdatum",
    ];

    private static $default_sort = 'SortField ASC, ID ASC';

    private static $inline_editable = false;

    private static $summary_fields = [
        'ID' => 'ID',
        'Title' => 'Titel',
    ];

    private static $searchable_fields = [
        "Title",
        "Text",
    ];

    private static $table_name = "PressReference";

    private static $singular_name = "Erwähnung";
    private static $plural_name = "Erwähnungen";


    // tidy up the CMS by not showing these fields
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeFieldFromTab("Root.Main", "SortField");
        return $fields;
    }

    public function canView($member = null)
    {
        return true;
    }

    public function canEdit($member = null)
    {
        return Permission::check('CMS_ACCESS_NewsAdmin', 'any', $member);
    }

    public function canDelete($member = null)
    {
        return Permission::check('CMS_ACCESS_NewsAdmin', 'any', $member);
    }

    public function canCreate($member = null, $context = [])
    {
        return Permission::check('CMS_ACCESS_NewsAdmin', 'any', $member);
    }
}
