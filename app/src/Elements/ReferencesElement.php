<?php

namespace App\Elements;

use App\Press\PressReference;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\ArrayData;

/**
 * Class \App\Elements\ReferencesElement
 *
 * @property ?string $Text
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class ReferencesElement extends BaseElement
{

    private static $db = [
        "Text" => "HTMLText",
    ];

    private static $field_labels = [
        "Text" => "Text",
    ];

    public function inlineEditable() {
        return false;
    }

    private static $table_name = 'ReferencesElement';
    private static $icon = 'sp-icon-news-element';

    public function getType()
    {
        return "Erwähnungen (Liste)";
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        return $fields;
    }

    public function getReferencesByYear()
    {
        $references = PressReference::get()->sort('PublishDate DESC, ID DESC');
        $grouped = [];

        foreach ($references as $ref) {
            $year = $ref->PublishDate ? date('Y', strtotime($ref->PublishDate)) : 'Unbekannt';
            $grouped[$year][] = $ref;
        }

        krsort($grouped);

        $result = ArrayList::create();
        foreach ($grouped as $year => $refs) {
            $result->push(ArrayData::create([
                'Year' => $year,
                'References' => ArrayList::create($refs),
            ]));
        }

        return $result;
    }
}
