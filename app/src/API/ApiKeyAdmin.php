<?php

namespace App\API;

use SilverStripe\Admin\ModelAdmin;

/**
 * Class \App\API\ApiKeyAdmin
 *
 */
class ApiKeyAdmin extends ModelAdmin
{
    private static $managed_models = [
        ApiKey::class,
    ];

    private static $url_segment = "api-keys";

    private static $menu_title = "API-Schlüssel";
}
