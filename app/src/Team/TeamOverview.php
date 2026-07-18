<?php

namespace App\Team;

use Page;
use SilverStripe\Assets\Image;

use SilverStripe\AssetAdmin\Forms\UploadField;

/**
 * Class \App\Team\TeamOverview
 *
 * @property int $HeaderImageID
 * @method Image HeaderImage()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class TeamOverview extends Page
{
    private static $table_name = 'TeamOverview';

    private static $has_one = [
        "HeaderImage" => Image::class,
    ];

    private static $owns = [
        "HeaderImage"
    ];

    private static $icon = "app/client/icons/teamgray.svg";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab("Root.Main", UploadField::create("HeaderImage", "Headerbild"));
        return $fields;
    }
}
