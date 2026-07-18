<?php

namespace App\Elements;

use App\Elements\HeroSliderItem;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\ORM\DB;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * Class \App\Elements\HeroSliderElement
 *
 * @property bool $UseRandomOrder
 * @property ?string $DateFrameTitle
 * @property ?string $DateFrameText
 * @property ?string $DateFrameSubText
 * @method DataList<HeroSliderItem> HeroSliderItems()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class HeroSliderElement extends BaseElement
{
    private static $db = [
        "UseRandomOrder" => "Boolean",
        "DateFrameTitle" => "Varchar(255)",
        "DateFrameText" => "Varchar(255)",
        "DateFrameSubText" => "Varchar(255)",
    ];

    private static $field_labels = [
    ];

    private static $has_many = [
        "HeroSliderItems" => HeroSliderItem::class,
    ];

    private static $owns = [
        "HeroSliderItems",
    ];

    public function inlineEditable()
    {
        return false;
    }

    private static $table_name = 'HeroSliderElement';
    private static $icon = 'sp-icon-hero-element';

    public function getType()
    {
        return "Hero Slider";
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName("HeroSliderItems");

        $gridFieldConfig = GridFieldConfig_RecordEditor::create(200);
        $gridfield = GridField::create("HeroSliderItems", "Hero Slider Items", $this->HeroSliderItems(), $gridFieldConfig);
        $gridfield->getConfig()->addComponent(GridFieldOrderableRows::create('SortOrder'));
        $fields->addFieldToTab('Root.Main', $gridfield);
        return $fields;
    }

    public function getSlidesInRandomOrder()
    {
        return $this->HeroSliderItems()->sort(DB::get_conn()->random());
    }
}
