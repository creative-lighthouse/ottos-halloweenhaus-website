<?php

namespace App\Elements;

use SilverStripe\ORM\DataList;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * Class \App\Elements\TimelineElement
 *
 * @property bool $IsCollapsible
 * @method DataList<FAQItem> FAQItems()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class FAQElement extends BaseElement
{

    private static $db = [
        "IsCollapsible" => "Boolean"
    ];

    private static $has_many = [
        "FAQItems" => FAQItem::class
    ];

    private static $owns = [
        "FAQItems"
    ];

    private static $cascade_duplicates = [
        "FAQItems"
    ];

    private static $field_labels = [
        "IsCollapsible" => "Ist einklappbar"
    ];

    private static $defaults = [
        "IsCollapsible" => "True",
    ];

    private static $table_name = 'FAQElement';
    private static $icon = 'sp-icon-qa-element';

    public function inlineEditable()
    {
        return false;
    }

    public function getType()
    {
        return _t(__CLASS__ . '.BlockType', 'FAQ-Liste');
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName("FAQItems");
        
        $itemsConfig = GridFieldConfig_RecordEditor::create();
        $itemsConfig->addComponent(new GridFieldOrderableRows('SortOrder'));
        $fields->addFieldToTab('Root.Main', GridField::create('FAQItems', 'Einträge', $this->FAQItems(), $itemsConfig));

        return $fields;
    }
}
