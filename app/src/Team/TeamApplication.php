<?php

namespace App\Team;

use App\Team\TeamApplicationInterest;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\SearchableDropdownField;
use SilverStripe\Forms\SearchableMultiDropdownField;
use SilverStripe\ORM\DataObject;

/**
 * Class \App\Team\TeamApplication
 *
 * @property ?string $Title
 * @property ?string $Birthday
 * @property ?string $Email
 * @property ?string $Hobbies
 * @property ?string $ReasonToJoin
 * @property ?string $Status
 * @method ManyManyList<TeamApplicationInterest> Interests()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class TeamApplication extends DataObject
{
    private static $db = [
        "Title" => "Varchar(255)",
        "Birthday" => "Varchar(255)",
        "Email" => "Varchar(255)",
        "Hobbies" => "Text",
        "ReasonToJoin" => "Text",
        "Status" => "Varchar(255)"
    ];

    private static $many_many = [
        "Interests" => TeamApplicationInterest::class
    ];

    private static $default_sort = "Status, Created ASC";

    private static $field_labels = [
        "Title" => "Vor- & Nachname",
        "Birthday" => "Geburtstag",
        "Email" => "E-Mail",
        "Hobbies" => "Hobbys",
        "ReasonToJoin" => "Warum möchtest du ins Team?",
        "Interests" => "Interessensgebiete",
        "Status" => "Status",
    ];

    private static $summary_fields = [
        "Title" => "Vor- & Nachname",
        "Email" => "E-Mail",
        "RenderStatus" => "Status"
    ];

    private static $searchable_fields = [
        "Title",
        "Email",
        "Hobbies",
        "ReasonToJoin",
    ];

    private static $table_name = "TeamApplication";

    private static $singular_name = "Team-Bewerbung";
    private static $plural_name = "Team-Bewerbungen";

    private static $url_segment = "teamapplication";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField('Status', DropdownField::create('Status', 'Status', [
            "new" => "Neu",
            "in Arbeit" => "Wird bearbeitet",
            "casting" => "für Casting angemeldet",
            "accepted" => "Angenommen",
            "rejected" => "Abgelehnt",
        ]));

        //Make Interests a SearchableDropdownField with the existing interests as options
        $fields->removeByName("Interests");
        $fields->addFieldToTab("Root.Main", SearchableMultiDropdownField::create("Interests", "Interessensgebiete", TeamApplicationInterest::get()));

        return $fields;
    }
}
