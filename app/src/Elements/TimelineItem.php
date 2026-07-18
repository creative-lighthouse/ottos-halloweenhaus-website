<?php

namespace App\Elements;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Security\Permission;

/**
 * Class \App\Elements\TimelineItem
 *
 * @property ?string $Year
 * @property ?string $Headline
 * @property ?string $Type
 * @property ?string $Text
 * @property int $SortOrder
 * @property int $ParentID
 * @method TimelineElement Parent()
 * @method DataList<PhotoGalleryImage> PhotoGalleryImages()
 * @mixin PhotoGalleryExtension
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class TimelineItem extends DataObject
{

    private static $db = [
        "Year" => "Varchar(50)",
        "Headline" => "Varchar(255)",
        "Type" => "Varchar(255)",
        "Text" => "HTMLText",
        "SortOrder" => "Int",
    ];

    private static $has_one = [
        "Parent" => TimelineElement::class
    ];

    private static $field_labels = [
        "Year" => "Jahr",
        "Headline" => "Überschrift",
        "Text" => "Text"
    ];

    private static $default_sort = 'SortOrder ASC, ID ASC';

    private static $summary_fields = [
        'ID' => 'ID',
        'Year' => 'Jahr',
        'Headline' => 'Überschrift'
    ];

    private static $searchable_fields = [
        "Year",
        "Headline"
    ];

    private static $table_name = 'TimelineItem';
    private static $singular_name = "Zeitleisten-Eintrag";
    private static $plural_name = "Zeitleisten-Einträge";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeFieldFromTab("Root.Main", "ParentID");
        $fields->replaceField('Type', DropdownField::create('Type', 'Typ', [
            "halloween" => "Halloween",
            "media" => "Medien",
            "milestone" => "Meilenstein",
            "other" => "Sonstiges",
        ]));
        $fields->removeFieldFromTab("Root.Main", "SortOrder");
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

    public function getFormattedType($usedType){
        switch($usedType){
            case 'halloween':
                return "Halloween";
            case 'media':
                return "Medien";
            case 'milestone':
                return "Meilenstein";
            case 'other':
                return "Anderes";
        }
    }
}
