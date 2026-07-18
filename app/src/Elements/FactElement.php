<?php

namespace App\Elements;

use SilverStripe\ORM\DataList;
use App\Elements\FactItem;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use UndefinedOffset\SortableGridField\Forms\GridFieldSortableRows;

/**
 * Class \App\Elements\ReportsElement
 *
 * @property ?string $Text
 * @method DataList<FactItem> FactItems()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class FactElement extends BaseElement
{

    private static $db = [
        "Text" => "HTMLText",
    ];

    private static $field_labels = [
        "Text" => "Text",
    ];

    private static $has_many = [
        "FactItems" => FactItem::class,
    ];

    public function inlineEditable()
    {
        return false;
    }

    private static $table_name = 'FactElement';
    private static $icon = 'sp-icon-counter-element';

    public function getType()
    {
        return "Fakten & Zahlen";
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName("FactItems");

        $gridFieldConfig = GridFieldConfig_RecordEditor::create(200);
        $sorter = GridFieldSortableRows::create('SortOrder');
        $gridFieldConfig->addComponent($sorter);
        $gridfield = GridField::create("FactItems", "Fakten", $this->FactItems(), $gridFieldConfig);
        $fields->addFieldToTab('Root.Main', $gridfield);
        return $fields;
    }
}
