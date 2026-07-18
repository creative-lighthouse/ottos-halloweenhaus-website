<?php

namespace App\Elements;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Assets\Image;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\Forms\DropdownField;

/**
 * Class \App\Elements\WikiElement
 *
 * @property ?string $Text
 * @property int $BannerImageID
 * @property int $Image1ID
 * @property int $Image2ID
 * @property int $Image3ID
 * @property int $Image4ID
 * @property int $ButtonID
 * @method Image BannerImage()
 * @method Image Image1()
 * @method Image Image2()
 * @method Image Image3()
 * @method Image Image4()
 * @method Link Button()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class WikiElement extends BaseElement
{
    private static $db = [
        "Text" => "HTMLText",
    ];

    private static $has_one = [
        "BannerImage" => Image::class,
        "Image1" => Image::class,
        "Image2" => Image::class,
        "Image3" => Image::class,
        "Image4" => Image::class,
        "Button" => Link::class,
    ];

    private static $owns = [
        "BannerImage",
        "Image1",
        "Image2",
        "Image3",
        "Image4",
        "Button",
    ];

    private static $field_labels = [
        "Text" => "Text",
        "BannerImage" => "Banner-Bild",
        "Image1" => "Bild 1",
        "Image2" => "Bild 2",
        "Image3" => "Bild 3",
        "Image4" => "Bild 4",
        "Button" => "Button",
    ];

    private static $table_name = 'WikiElement';
    private static $icon = 'sp-icon-tutorial-element';

    public function getType()
    {
        return "Wiki";
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        return $fields;
    }
}
