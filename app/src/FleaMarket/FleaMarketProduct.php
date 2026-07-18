<?php

namespace App\FleaMarket;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;
use App\FleaMarket\FleaMarketAdmin;
use SilverStripe\Forms\CheckboxSetField;

/**
 * Class \App\FleaMarket\FleaMarketProduct
 *
 * @property ?string $Title
 * @property ?string $ProductCode
 * @property ?string $Description
 * @property float $Price
 * @property int $Stock
 * @property bool $NegotiablePrice
 * @property bool $Visible
 * @property ?string $Size
 * @property ?string $Material
 * @property ?string $Color
 * @property ?string $Brand
 * @property ?string $Condition
 * @property ?string $Weight
 * @method DataList<PhotoGalleryImage> PhotoGalleryImages()
 * @method ManyManyList<FleaMarketProductCategory> Categories()
 * @mixin PhotoGalleryExtension
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class FleaMarketProduct extends DataObject
{
    private static $db = [
        "Title" => "Varchar(255)",
        "ProductCode" => "Varchar(5)",
        "Description" => "HTMLText",
        "Price" => "Double",
        "Stock" => "Int",
        "NegotiablePrice" => "Boolean",
        "Visible" => "Boolean",
        "Size" => "Varchar(255)",
        "Material" => "Varchar(255)",
        "Color" => "Varchar(255)",
        "Brand" => "Varchar(255)",
        "Condition" => "Varchar(255)",
        "Weight" => "Varchar(255)",
    ];

    private static $many_many = [
        "Categories" => FleaMarketProductCategory::class,
    ];

    private static $default_sort = "Title ASC";

    private static $field_labels = [
        "Title" => "Name",
        "ProductCode" => "Produktcode",
        "Description" => "Beschreibung",
        "Price" => "Preis",
        "Stock" => "Lagerbestand",
        "NegotiablePrice" => "Preis verhandelbar",
        "Visible" => "Sichtbar",
        "Size" => "Größe (optional)",
        "Material" => "Material (optional)",
        "Color" => "Farbe (optional)",
        "Brand" => "Marke (optional)",
        "Model" => "Modell (optional)",
        "Condition" => "Zustand (optional)",
        "Categories" => "Kategorien",
        "Weight" => "Gewicht (optional)",
    ];

    private static $summary_fields = [
        "Thumbnail" => "Bild",
        "Title" => "Name",
        "ProductCode" => "Produktcode",
        "FormattedPrice" => "Preis",
        "Stock" => "Bestand",
    ];

    private static $searchable_fields = [
        "Title",
        "ProductCode",
        "Description",
        "Price",
        "Stock",
    ];

    private static $table_name = "FleaMarketProduct";

    private static $singular_name = "Produkt";
    private static $plural_name = "Produkte";

    private static $url_segment = "product";

    private static $defaults = [
        "NegotiablePrice" => true,
        "Visible" => true,
    ];

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->ProductCode) {
            $this->ProductCode = strtoupper(substr(md5($this->Title), 0, 5));
        }
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName("Categories");
        $fields->addFieldToTab("Root.Main", CheckboxSetField::create(
            "Categories",
            "Kategorien",
            FleaMarketProductCategory::get()->map("ID", "Title")
        ));
        return $fields;
    }

    public function CMSEditLink()
    {
        $admin = FleaMarketAdmin::singleton();
        $urlClass = str_replace('\\', '-', self::class);
        return $admin->Link("/{$urlClass}/EditForm/field/{$urlClass}/item/{$this->ID}/edit");
    }

    public function getFormattedPrice()
    {
        if (!$this->Price || $this->Price == 0) {
            return "kostenlos";
        }
        if ($this->Price < 0) {
            return "Preis auf Anfrage";
        }
        return number_format($this->Price, 2, ",", ".") . " €";
    }

    public function getThumbnail()
    {
        if ($this->PhotoGalleryImages()->first()) {
            $image = $this->PhotoGalleryImages()->first()->Image();
            if ($image) {
                return $image->CMSThumbnail();
            }
        }
        return Image::create()->CMSThumbnail();
    }

    public function getCategoriesList()
    {
        return implode(", ", $this->Categories()->column("Title"));
    }

    public function getCategories()
    {
        return $this->Categories()->column("ID");
    }

    public function getLink()
    {
        $fleamarketpageholder = FleaMarketPage::get()->first();
        if ($fleamarketpageholder) {
            return $fleamarketpageholder->Link("view/{$this->ID}");
        }
        return "";
    }

    public function getImage()
    {
        if ($this->PhotoGalleryImages()->first()) {
            return $this->PhotoGalleryImages()->first()->Image();
        } else {
            return false;
        }
    }
}
