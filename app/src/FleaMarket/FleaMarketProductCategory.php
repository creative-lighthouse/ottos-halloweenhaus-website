<?php

namespace App\FleaMarket;

use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\DataObject;
use App\FleaMarket\FleaMarketAdmin;
use App\FleaMarket\FleaMarketProduct;

/**
 * Class \App\FleaMarket\FleaMarketProductCategory
 *
 * @property ?string $Title
 * @property ?string $Description
 * @method ManyManyList<FleaMarketProduct> Products()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class FleaMarketProductCategory extends DataObject
{
    private static $db = [
        "Title" => "Varchar(255)",
        "Description" => "HTMLText",
    ];

    private static $belongs_many_many = [
        "Products" => FleaMarketProduct::class,
    ];

    private static $default_sort = "Title ASC";

    private static $field_labels = [
        "Title" => "Name",
        "Description" => "Beschreibung",
    ];

    private static $summary_fields = [
        "Title" => "Name",
    ];

    private static $searchable_fields = [
        "Title",
        "Description",
    ];

    private static $table_name = "FleaMarketProductCategory";

    private static $singular_name = "Kategorie";
    private static $plural_name = "Kategorien";

    private static $url_segment = "productcategory";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        return $fields;
    }

    public function CMSEditLink()
    {
        $admin = FleaMarketAdmin::singleton();
        $urlClass = str_replace('\\', '-', self::class);
        return $admin->Link("/{$urlClass}/EditForm/field/{$urlClass}/item/{$this->ID}/edit");
    }

    public function getItems()
    {
        return $this->Products()->filter("Visible", true);
    }

    public function Link()
    {
        $fleamarketpageholder = FleaMarketPage::get()->first();
        if ($fleamarketpageholder) {
            return $fleamarketpageholder->Link("category/{$this->ID}");
        }
        return "";
    }
}
