<?php

namespace App\Elements;

use SilverStripe\ORM\DataList;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use UndefinedOffset\SortableGridField\Forms\GridFieldSortableRows;

/**
 * Class \App\Elements\TimelineElement
 *
 * @property bool $IsCollapsible
 * @method DataList<TimelineItem> TimelineItems()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class TimelineElement extends BaseElement
{

    private static $db = [
        "IsCollapsible" => "Boolean"
    ];

    private static $has_many = [
        "TimelineItems" => TimelineItem::class
    ];

    private static $field_labels = [
        "IsCollapsible" => "Ist einklappbar"
    ];

    private static $defaults = [
        "IsCollapsible" => "True",
    ];

    private static $table_name = 'TimelineElement';
    private static $icon = 'sp-icon-clock-element';

    public function inlineEditable()
    {
        return false;
    }

    public function getType()
    {
        return _t(__CLASS__ . '.BlockType', 'Zeitleiste');
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName("TimelineItems");

        $gridFieldConfig = GridFieldConfig_RecordEditor::create(200);
        $sorter = GridFieldSortableRows::create('SortOrder');
        $gridFieldConfig->addComponent($sorter);
        $gridfield = GridField::create("Items", "Eintrag", $this->TimelineItems(), $gridFieldConfig);
        $fields->addFieldToTab('Root.Main', $gridfield);

        return $fields;
    }
}
