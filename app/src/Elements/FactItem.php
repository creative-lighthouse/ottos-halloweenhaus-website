<?php

namespace App\Elements;

use App\Elements\FactElement;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;

/**
 * Class \App\Elements\TeaserItem
 *
 * @property ?string $Title
 * @property int $Number
 * @property ?string $Text
 * @property int $SortOrder
 * @property int $ParentID
 * @method FactElement Parent()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class FactItem extends DataObject
{
    private static $db = [
        "Title" => "Varchar(255)",
        "Number" => "Int",
        "Text" => "HTMLText",
        "SortOrder" => "Int",
    ];

    private static $has_one = [
        "Parent" => FactElement::class,
    ];

    private static $field_labels = [
        "Title" => "Titel",
        "Number" => "Zahl",
        "Text" => "Text",
    ];

    private static $default_sort = 'SortOrder ASC, ID ASC';

    private static $inline_editable = false;

    private static $summary_fields = [
        'ID' => 'ID',
        'Title' => 'Titel',
        'Number' => 'Zahl',
    ];

    private static $searchable_fields = [
        "Title",
        "Text",
        "Number"
    ];

    private static $table_name = "FactItem";

    private static $singular_name = "Fakt";
    private static $plural_name = "Fakten";


    // tidy up the CMS by not showing these fields
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeFieldFromTab("Root.Main", "ParentID");
        $fields->removeFieldFromTab("Root.Main", "SortOrder");
        $fields->removeByName("ButtonID");
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
