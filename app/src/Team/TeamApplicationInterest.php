<?php

namespace App\Team;

use App\Wiki\Show;
use App\Wiki\MediaProject;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\DropdownField;

/**
 * Class \App\Team\TeamApplicationInterest
 *
 * @property ?string $Title
 * @method ManyManyList<TeamApplication> Applications()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class TeamApplicationInterest extends DataObject
{
    private static $db = [
        "Title" => "Varchar(255)",
    ];

    private static $belongs_many_many = [
        "Applications" => TeamApplication::class
    ];

    private static $default_sort = "Title ASC";

    private static $field_labels = [
    ];

    private static $summary_fields = [
        "Title" => "Name"
    ];

    private static $searchable_fields = [
        "Title",
    ];

    private static $table_name = "TeamApplicationInterest";

    private static $singular_name = "Bewerbungsinteresse";
    private static $plural_name = "Bewerbungsinteressen";

    private static $url_segment = "teamapplicationinterest";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        return $fields;
    }
}
