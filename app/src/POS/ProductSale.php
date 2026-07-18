<?php

namespace App\POS;

use App\Feedback\FeedbackAdmin;
use SilverStripe\ORM\DataObject;

/**
 * Class \App\POS\ProductSale
 *
 * @property int $Amount
 * @property float $SellingPrice
 * @property int $ParentID
 * @property int $ProductID
 * @method Sale Parent()
 * @method Product Product()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class ProductSale extends DataObject
{
    private static $db = [
        "Amount" => "Int",
        "SellingPrice" => "Double",
    ];

    private static $has_one = [
        "Parent" => Sale::class,
        "Product" => Product::class,
    ];

    private static $default_sort = "ID ASC";

    private static $field_labels = [
        "Product" => "Produkt",
        "Amount" => "Menge",
    ];

    private static $summary_fields = [
        "ID" => "ID",
        "Product.Title" => "Produkt",
        "Amount" => "Menge",
        "TotalPrice" => "Gesamtpreis",
    ];

    private static $defaults = [
        "SellingPrice" => -1,
    ];

    private static $table_name = "ProductSale";

    private static $singular_name = "Verkaufsposition";
    private static $plural_name = "Verkaufspositionen";

    private static $url_segment = "productsale";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName("ParentID");
        return $fields;
    }

    public function CMSEditLink()
    {
        $admin = FeedbackAdmin::singleton();
        $urlClass = str_replace('\\', '-', self::class);
        return $admin->Link("/{$urlClass}/EditForm/field/{$urlClass}/item/{$this->ID}/edit");
    }

    /**
     * Event handler called before writing to the database.
     *
     * @uses DataExtension->onAfterWrite()
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if ($this->SellingPrice == -1) {
            $this->SellingPrice = $this->Product()->Price;
        }
    }

    public function getTotalPrice()
    {
        return $this->SellingPrice * $this->Amount . " €";
    }
}
