<?php

namespace App\ImageBooth;

use App\ImageBooth\BoothImage;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\ORM\DataList;
use Colymba\BulkManager\BulkManager;
use SilverStripe\Forms\GridField\GridFieldConfig;

/**
 * Class \App\ImageBooth\ImageBoothAdmin
 *
 */
class ImageBoothAdmin extends ModelAdmin
{

    private static $managed_models = array(
        BoothImage::class,
    );

    private static $url_segment = "imagebooth";

    private static $menu_title = "Fotobox";

    private static $menu_icon = "app/client/icons/imagebooth_admin.svg";

    public function init()
    {
        parent::init();
    }

    public function getList(): DataList
    {
        // Onride photos are managed in OnrideImageAdmin
        return parent::getList()->filter("ClassName", BoothImage::class);
    }

    protected function getGridFieldConfig(): GridFieldConfig
    {
        $config = parent::getGridFieldConfig();

        $config->addComponent(BulkManager::create(), 'GridFieldEditButton');

        return $config;
    }
}
