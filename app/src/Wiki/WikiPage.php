<?php

namespace App\Wiki;

use Page;
use SilverStripe\Assets\Image;

/**
 * Class \App\Wiki\WikiPage
 *
 * @property ?string $Description
 * @property int $ImageID
 * @method Image Image()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class WikiPage extends Page
{
    private static $db = [
        "Description" => "Text",
    ];

    private static $has_one = [
        "Image" => Image::class,
    ];

    private static $owns = [
        "Image",
    ];

    private static $table_name = 'WikiPage';

    private static $icon = "_resources/app/client/icons/wiki.svg";
    private static $cms_icon_class = 'font-icon-p-posts';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        return $fields;
    }
}
