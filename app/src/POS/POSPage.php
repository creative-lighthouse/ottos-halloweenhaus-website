<?php

namespace App\POS;

use SilverStripe\AssetAdmin\Forms\UploadField;

use SilverStripe\Assets\Image;

use SilverStripe\CMS\Model\SiteTree;

/**
 * Class \App\POS\POSPage
 *
 * @property int $BackgroundImageID
 * @method Image BackgroundImage()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class POSPage extends SiteTree
{
    private static $db = [];

    private static $has_one = [
        "BackgroundImage" => Image::class,
    ];

    private static $owns = [
        "BackgroundImage"
    ];

    private static $icon = "app/client/icons/pos_page.svg";
    private static $table_name = "POSPage";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab("Root.Main", UploadField::create("BackgroundImage", "Hintergrundbild"));
        return $fields;
    }
}
