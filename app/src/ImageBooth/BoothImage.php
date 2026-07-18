<?php

namespace App\ImageBooth;

use DateTime;
use SilverStripe\Assets\File;
use SilverStripe\ORM\DataObject;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\ErrorCorrectionLevel;

/**
 * Class \App\ImageBooth\BoothImage
 *
 * @property bool $isVisible
 * @property ?string $HashID
 * @property ?string $GroupID
 * @property int $ImageID
 * @method File Image()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class BoothImage extends DataObject
{
    private static $db = [
        "isVisible" => "Boolean",
        "HashID" => "Varchar(5)",
        "GroupID" => "Varchar(128)"
    ];

    private static $has_one = [
        "Image" => File::class
    ];

    private static $owns = [
        "Image"
    ];

    private static $default_sort = "Created DESC";

    private static $field_labels = [
        "isVisible" => "Sichtbar in Gallerie",
        "Base64Image" => "Bild-Code",
    ];

    private static $summary_fields = [
        "Thumbnail" => "Thumbnail",
        "FormattedIsVisible" => "Sichtbar",
        "FormattedCreationDate" => "Erstellt am"
    ];

    private static $table_name = "BoothImage";

    private static $singular_name = "Fotobox-Bild";
    private static $plural_name = "Fotobox Bilder";

    private static $url_segment = "boothimage";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $now = new DateTime();
        $now = $now->format("Y-m-d H:i:s");

        if (!$this->HashID) {
            $this->HashID = substr(md5(string: $now . $this->ID), 0, 5);
        }
    }

    public function getThumbnail()
    {
        $image = $this->Image();
        if ($image && $image->exists()) {
            return $image->CMSThumbnail();
        }
        return null;
    }

    public function getFormattedCreationDate()
    {
        return $this->dbObject("Created")->Format("dd.MM.YYYY HH:mm:ss");
    }

    public function getFormattedIsVisible()
    {
        return $this->isVisible ? "Ja" : "Nein";
    }

    public function getQRCode()
    {
        $adminPage = PhotoboxGalleryPage::get()->first();
        if ($adminPage) {
            $validateLink = $adminPage->AbsoluteLink("foto") . "/" . $this->HashID;
        } else {
            $validateLink = "/404";
        }

        $qrCode = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($validateLink)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->validateResult(false)
            ->build();
        header('Content-Type: ' . $qrCode->getMimeType());
        return $qrCode->getDataUri();
    }
}
