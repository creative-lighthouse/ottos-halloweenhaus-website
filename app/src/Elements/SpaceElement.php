<?php

namespace App\Elements;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\DropdownField;

/**
 * Class \App\Elements\SpaceElement
 *
 * @property int $Height
 * @property ?string $Variant
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class SpaceElement extends BaseElement
{

    private static $db = [
        "Height" => "Int(1000)",
        "Variant" => "Varchar(255)",
    ];

    private static $field_labels = [
        "Height" => "Höhe (in px)",
        "Variant" => "Variante"
    ];

    private static $table_name = 'SpaceElement';
    private static $icon = 'sp-icon-virtual-element';

    public function getType() { return "Abstand"; }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.Main', DropdownField::create('Variant', 'Variante', [
            "variant--empty" => "Leerer Abstand",
            "variant--divider" => "Trennlinie",
        ]));

        return $fields;
    }
}
