<?php

namespace App\ShowController;

use SilverStripe\Admin\ModelAdmin;
use Colymba\BulkManager\BulkManager;
use SilverStripe\Forms\GridField\GridFieldConfig;

/**
 * Class \App\ShowController\ShowControllerAdmin
 *
 */
class ShowControllerAdmin extends ModelAdmin
{
    private static $managed_models = [
        ShowControllerEntry::class,
    ];

    private static $url_segment = "show-controller";

    private static $menu_title = "Show-Controller";

    protected function getGridFieldConfig(): GridFieldConfig
    {
        $config = parent::getGridFieldConfig();

        $config->addComponent(BulkManager::create(), 'GridFieldEditButton');

        return $config;
    }
}
