<?php

namespace App\Elements;

use SilverStripe\ORM\DataList;
use App\Elements\TeaserItem;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use UndefinedOffset\SortableGridField\Forms\GridFieldSortableRows;

/**
 * Class \App\Elements\TeaserElement
 *
 * @property ?string $Text
 * @method DataList<TeaserItem> TeaserItems()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class TeaserElement extends BaseElement
{
    private static $db = [
        "Text" => "HTMLText",
    ];

    private static $field_labels = [
        "Text" => "Text",
    ];

    private static $has_many = [
        "TeaserItems" => TeaserItem::class,
    ];

    public function inlineEditable()
    {
        return false;
    }

    private static $table_name = 'TeaserElement';
    private static $icon = 'sp-icon-teaser-element';

    public function getType()
    {
        return "Teaser";
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName("TeaserItems");

        $gridFieldConfig = GridFieldConfig_RecordEditor::create(200);
        $gridfield = GridField::create("TeaserItems", "Teaser", $this->TeaserItems(), $gridFieldConfig);
        $gridfield->getConfig()->addComponent(GridFieldOrderableRows::create('SortOrder'));
        $fields->addFieldToTab('Root.Main', $gridfield);
        return $fields;
    }
}
