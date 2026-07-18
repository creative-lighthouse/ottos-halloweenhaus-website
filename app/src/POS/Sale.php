<?php

namespace App\POS;

use SilverStripe\ORM\DataList;
use App\Feedback\FeedbackAdmin;
use SilverStripe\ORM\DataObject;

/**
 * Class \App\POS\Sale
 *
 * @property float $TotalPrice
 * @property ?string $SaleTime
 * @method DataList<ProductSale> ProductSales()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class Sale extends DataObject
{
    private static $db = [
        "TotalPrice" => "Double",
        "SaleTime" => "Datetime",
    ];

    private static $has_many = [
        "ProductSales" => ProductSale::class,
    ];

    private static $owns = [
        "ProductSales",
    ];

    private static $default_sort = "ID ASC";

    private static $field_labels = [
        "TotalPrice" => "Gesamtpreis",
        "SaleTime" => "Verkaufszeit",
    ];

    private static $summary_fields = [
        "ID" => "ID",
        "FormattedPrice" => "Gesamtpreis",
        "FormattedDateTime" => "Verkaufszeit",
    ];

    private static $table_name = "Sale";

    private static $singular_name = "Verkauf";
    private static $plural_name = "Verkäufe";

    private static $url_segment = "sale";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
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
        $totalPrice = 0;

        foreach ($this->ProductSales() as $productSale) {
            $totalPrice += $productSale->SellingPrice * $productSale->Amount;
        }

        $this->TotalPrice = $totalPrice;
    }

    public function getFormattedPrice()
    {
        if ($this->TotalPrice == 0) {
            return "Spendenbasis";
        }
        return $this->TotalPrice . " €";
    }

    public function getFormattedDateTime()
    {
        return date("d.m.Y H:i", strtotime($this->SaleTime));
    }
}
