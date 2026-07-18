<?php

namespace App\Elements;

use App\Elements\HeroSliderElement;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;

/**
 * Class \App\Elements\HeroSliderItem
 *
 * @property ?string $Title
 * @property int $SortOrder
 * @property int $ParentID
 * @property int $ImageID
 * @method HeroSliderElement Parent()
 * @method Image Image()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class HeroSliderItem extends DataObject
{
    private static $db = [
        "Title" => "Varchar(255)",
        "SortOrder" => "Int",
    ];

    private static $has_one = [
        "Parent" => HeroSliderElement::class,
        "Image" => Image::class,
    ];

    private static $owns = [
        "Image",
    ];

    private static $field_labels = [
        "Title" => "Titel",
        "Image" => "Bild",
    ];

    private static $default_sort = 'SortOrder ASC, ID ASC';

    private static $inline_editable = false;

    private static $summary_fields = [
        "Image.CMSThumbnail" => 'Bild',
    ];

    private static $searchable_fields = [
        "Title",
    ];

    private static $table_name = "HeroSliderItem";

    private static $singular_name = "Hero Bild";
    private static $plural_name = "Hero Bilder";


    // tidy up the CMS by not showing these fields
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeFieldFromTab("Root.Main", "ParentID");
        $fields->removeFieldFromTab("Root.Main", "SortOrder");
        return $fields;
    }

    public function canView($member = null)
    {
        return true;
    }
}
