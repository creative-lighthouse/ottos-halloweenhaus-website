<?php

namespace App\Elements;

use DNADesign\Elemental\Models\BaseElement;

/**
 * Class \App\Elements\EmbedElement
 *
 * @property ?string $EmbedCode
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class EmbedElement extends BaseElement
{

    private static $db = [
        "EmbedCode" => "Text",
    ];

    private static $table_name = 'EmbedElement';
    private static $icon = 'sp-icon-material-element';

    public function getType()
    {
        return _t(__CLASS__ . '.BlockType', 'Embed');
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        return $fields;
    }
}
