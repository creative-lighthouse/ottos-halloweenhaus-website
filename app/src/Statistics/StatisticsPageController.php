<?php

namespace App\Statistics;

use PageController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Security\Security;

/**
 * Class \App\Statistics\StatisticsPageController
 *
 * @property StatisticsPage $dataRecord
 * @method StatisticsPage data()
 * @mixin StatisticsPage
 */
class StatisticsPageController extends PageController
{

    protected function init()
    {
        parent::init();
        HTTPCacheControlMiddleware::singleton()->disableCache();
    }

    private static $allowed_actions = [
        'index'
    ];

    public function index(HTTPRequest $request)
    {
        $currentUser = Security::getCurrentUser();
        /*if (!$currentUser) {
            return $this->redirect("/login");
        }*/
        return array();
    }
}
