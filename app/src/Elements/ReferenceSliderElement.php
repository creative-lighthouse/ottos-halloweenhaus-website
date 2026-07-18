<?php

namespace App\Elements;

use App\Press\PressReference;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\LinkField\Models\Link;

/**
 * Class \App\Elements\ReferenceSlider
 *
 * @property ?string $Text
 * @property int $ButtonID
 * @method Link Button()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class ReferenceSliderElement extends BaseElement
{

    private static $db = [
        "Text" => "HTMLText",
    ];

    private static $field_labels = [
        "Text" => "Text",
    ];

    private static $has_one = [
        "Button" => Link::class,
    ];

    private static $owns = [
        "Button",
    ];

    private static $table_name = 'ReferenceSliderElement';
    private static $icon = 'sp-icon-slider-element';

    public function getType()
    {
        return "Erwähnungen (Slider)";
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        return $fields;
    }

    public function ReferenceItems()
    {
        return PressReference::get()->filter("OnSlider", true)->sort("SortField ASC, ID ASC");
    }
}
