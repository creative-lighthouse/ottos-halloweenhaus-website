<?php

namespace App\ImageBooth;

use App\ImageBooth\OnrideImage;
use SilverStripe\Admin\ModelAdmin;
use Colymba\BulkManager\BulkManager;
use SilverStripe\Forms\GridField\GridFieldConfig;

/**
 * Class \App\ImageBooth\OnrideImageAdmin
 *
 */
class OnrideImageAdmin extends ModelAdmin
{

    private static $managed_models = array(
        OnrideImage::class,
    );

    private static $url_segment = "onrideimages";

    private static $menu_title = "Onride-Fotos";

    private static $menu_icon = "app/client/icons/imagebooth_admin.svg";

    protected function getGridFieldConfig(): GridFieldConfig
    {
        $config = parent::getGridFieldConfig();

        $config->addComponent(BulkManager::create(), 'GridFieldEditButton');

        return $config;
    }
}
