<?php

namespace App\Elements;

use SilverStripe\Assets\Image;
use SilverStripe\Security\Permission;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\ORM\DataObject;

/**
 * Class \App\Elements\TeaserItem
 *
 * @property ?string $Title
 * @property ?string $Text
 * @property int $SortOrder
 * @property int $ParentID
 * @property int $ImageID
 * @property int $ButtonID
 * @method TeaserElement Parent()
 * @method Image Image()
 * @method Link Button()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class TeaserItem extends DataObject
{
    private static $db = [
        "Title" => "Varchar(255)",
        "Text" => "HTMLText",
        "SortOrder" => "Int",
    ];

    private static $has_one = [
        "Parent" => TeaserElement::class,
        "Image" => Image::class,
        "Button" => Link::class,
    ];

    private static $owns = [
        "Image",
        "Button",
    ];

    private static $field_labels = [
        "Title" => "Titel",
        "Text" => "Text",
        "Image" => "Bild",
    ];

    private static $default_sort = 'SortOrder ASC, ID ASC';

    private static $inline_editable = false;

    private static $summary_fields = [
        "Image.CMSThumbnail" => 'Bild',
        'Title' => 'Titel',
    ];

    private static $searchable_fields = [
        "Title",
        "Text",
    ];

    private static $table_name = "TeaserItem";

    private static $singular_name = "Teaser";
    private static $plural_name = "Teaser";


    // tidy up the CMS by not showing these fields
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeFieldFromTab("Root.Main", "ParentID");
        $fields->removeFieldFromTab("Root.Main", "SortOrder");
        $fields->removeByName("ButtonID");
        $fields->insertAfter('Image', LinkField::create('Button'));
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
