<?php

namespace App\Elements;

use SilverStripe\Assets\Image;
use DNADesign\Elemental\Models\BaseElement;

/**
 * Class \App\Elements\HeroVideoElement
 *
 * @property ?string $EmbedCode
 * @property ?string $DirectVideo
 * @property ?string $DateFrameTitle
 * @property ?string $DateFrameText
 * @property ?string $DateFrameSubText
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class HeroVideoElement extends BaseElement
{

    private static $db = [
        "EmbedCode" => "Text",
        "DirectVideo" => "Varchar(512)",
        "DateFrameTitle" => "Varchar(255)",
        "DateFrameText" => "Varchar(255)",
        "DateFrameSubText" => "Varchar(255)",
    ];

    private static $field_labels = [];

    private static $table_name = 'HeroVideoElement';
    private static $icon = 'sp-icon-intro-element';

    public function getType()
    {
        return "Hero-Video";
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        return $fields;
    }
}
