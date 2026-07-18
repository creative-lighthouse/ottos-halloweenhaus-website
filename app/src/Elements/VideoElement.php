<?php

namespace App\Elements;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\DropdownField;

/**
 * Class \App\Elements\TextImageElement
 *
 * @property ?string $Text
 * @property ?string $VideoLink
 * @property ?string $Width
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class VideoElement extends BaseElement
{

    private static $db = [
        "Text" => "HTMLText",
        "VideoLink" => "Varchar(255)",
        "Width" => "Varchar(255)",
    ];

    private static $field_labels = [
        "Text" => "Text",
        "VideoLink" => "Video-Link (Youtube)",
        "Width" => "Video-Breite"
    ];

    private static $inline_editable = true;

    private static $table_name = 'VideoElement';
    private static $icon = 'sp-icon-video-element';

    public function getType()
    {
        return "Video";
    }

    public function provideBlockSchema()
    {
        $blockSchema = parent::provideBlockSchema();
        $blockSchema['content'] = $this->Text ? $this->dbObject('Text')->Plain() : "Kein Text";
        return $blockSchema;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.Main', DropdownField::create('Width', 'Video-Breite', [
            '100%' => '100% Breite',
            '85%' => '85% Breite',
            '75%' => '75% Breite',
            '50%' => '50% Breite',
            '25%' => '25% Breite',
        ]));

        return $fields;
    }
}
