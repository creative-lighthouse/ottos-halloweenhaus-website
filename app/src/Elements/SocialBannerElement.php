<?php

namespace App\Elements;

use SilverStripe\ORM\DataList;
use App\Elements\SocialBannerItem;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use UndefinedOffset\SortableGridField\Forms\GridFieldSortableRows;

/**
 * Class \App\Elements\TextImageElement
 *
 * @property ?string $Text
 * @method DataList<SocialBannerItem> Socials()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class SocialBannerElement extends BaseElement
{

    private static $db = [
        "Text" => "Varchar(255)",
    ];

    private static $has_many = [
        "Socials" => SocialBannerItem::class,
    ];

    private static $owns = [
        "Socials"
    ];

    private static $field_labels = [
        "Text" => "Text",
    ];

    public function inlineEditable() {
        return false;
    }

    private static $table_name = 'SocialBannerElement';
    private static $icon = 'sp-icon-social-element';

    public function getType()
    {
        return "Soziales Banner";
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName("Socials");

        $gridFieldConfig = GridFieldConfig_RecordEditor::create(200);
        $sorter = GridFieldSortableRows::create('SortOrder');
        $gridFieldConfig->addComponent($sorter);
        $gridfield = GridField::create("Socials", "Soziale Links", $this->Socials(), $gridFieldConfig);
        $fields->addFieldToTab( 'Root.Main', $gridfield );
        return $fields;
    }
}
