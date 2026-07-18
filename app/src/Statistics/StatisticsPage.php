<?php

namespace App\Statistics;

use Page;

/**
 * Class \App\Statistics\StatisticsPage
 *
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class StatisticsPage extends Page
{
    private static $table_name = 'StatisticsPage';

    private static $icon = "app/client/icons/statistics.svg";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        return $fields;
    }
}
