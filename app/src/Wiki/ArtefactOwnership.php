<?php

namespace App\Wiki;

use App\Wiki\Artefact;
use App\Wiki\Character;
use SilverStripe\ORM\DataObject;

/**
 * Class \App\Wiki\ArtefactOwnership
 *
 * @property ?string $StartTime
 * @property ?string $EndTime
 * @property ?string $Description
 * @property int $ParentID
 * @property int $CharacterID
 * @method Artefact Parent()
 * @method Character Character()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class ArtefactOwnership extends DataObject
{
    private static $db = [
        "StartTime" => "Varchar(255)",
        "EndTime" => "Varchar(255)",
        "Description" => "HTMLText",
    ];

    private static $has_one = [
        "Parent" => Artefact::class,
        "Character" => Character::class,
    ];

    private static $default_sort = "CharacterID ASC";

    private static $field_labels = [
        "Character" => "Besitzer",
        "Parent" => "Artefakt",
        "StartTime" => "Besitz von",
        "EndTime" => "Besitz bis",
        "Description" => "Beschreibung",
    ];

    private static $summary_fields = [
        "Character.Title" => "Besitzer",
        "Parent.Title" => "Artefakt",
        "RenderOwnershipTimespan" => "Besitzzeitraum",
    ];

    private static $searchable_fields = [
        "Character.Title",
        "Parent.Title",
    ];

    private static $table_name = "ArtefactOwnerships";

    private static $singular_name = "Artefaktbesitzer";
    private static $plural_name = "Artefaktbesitzer";

    private static $url_segment = "artefactownership";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        return $fields;
    }

    public function CMSEditLink()
    {
        $admin = WikiAdmin::singleton();
        $urlClass = str_replace('\\', '-', self::class);
        return $admin->Link("/{$urlClass}/EditForm/field/{$urlClass}/artefactownership/{$this->ID}/edit");
    }

    public function RenderOwnershipTimespan()
    {
        $start = $this->StartTime ? $this->StartTime : "Unbekannt";
        $end = $this->EndTime ? $this->EndTime : "Heute";
        return "{$start} - {$end}";
    }
}
