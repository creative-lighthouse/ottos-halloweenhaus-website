<?php

namespace App\Elements;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Assets\Image;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\Forms\DropdownField;

/**
 * Class \App\Elements\TextImageElement
 *
 * @property ?string $Text
 * @property ?string $Variant
 * @property bool $ImageIsLinked
 * @property bool $ImageLightbox
 * @property ?string $Embed
 * @property int $ImageID
 * @property int $ButtonID
 * @property int $SecondaryButtonID
 * @method Image Image()
 * @method Link Button()
 * @method Link SecondaryButton()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class TextImageElement extends BaseElement
{
    private static $db = [
        "Text" => "HTMLText",
        "Variant" => "Varchar(50)",
        "ImageIsLinked" => "Boolean",
        "ImageLightbox" => "Boolean",
        "Embed" => "Text",
    ];

    private static $has_one = [
        "Image" => Image::class,
        "Button" => Link::class,
        "SecondaryButton" => Link::class,
    ];

    private static $owns = [
        "Image",
        "Button",
        "SecondaryButton",
    ];

    private static $field_labels = [
        "Text" => "Text",
        "Image" => "Bild",
        "Button" => "Primärer Button",
        "SecondaryButton" => "Sekundärer Button",
        "ImageIsLinked" => "Bild verlinkt auch (zum Button-Link)",
        "ImageLightbox" => "Bild öffnet Lightbox",
        "Embed" => "Embed-Code",
    ];

    private static $table_name = 'TextImageElement';
    private static $icon = 'sp-icon-textimage-element';

    public function getType()
    {
        return "Text + Bild";
    }

    public function getYoutubeVideoId(): ?string
    {
        if (!$this->Embed) {
            return null;
        }
        if (preg_match('/youtube(?:-nocookie)?\.com\/embed\/([a-zA-Z0-9_-]+)/', $this->Embed, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName("ButtonID");
        $fields->removeByName("SecondaryButtonID");
        $fields->insertAfter('Button', LinkField::create('Button'));
        $fields->insertAfter('SecondaryButton', LinkField::create('SecondaryButton'));
        $fields->replaceField('Variant', DropdownField::create('Variant', 'Variante', [
            "image--left" => "Bild links",
            "image--right" => "Bild rechts",
        ]));
        return $fields;
    }
}
