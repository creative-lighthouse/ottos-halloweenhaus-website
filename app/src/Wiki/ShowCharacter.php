<?php

namespace App\Wiki;

use App\Team\TeamMember;
use App\Wiki\Character;
use App\Wiki\Show;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;

/**
 * Class \App\Wiki\ShowCharacter
 *
 * @property ?string $Note
 * @property int $ParentID
 * @property int $CharacterID
 * @property int $TeamMemberID
 * @property int $RoleImageID
 * @method Show Parent()
 * @method Character Character()
 * @method TeamMember TeamMember()
 * @method Image RoleImage()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class ShowCharacter extends DataObject
{
    private static $db = [
        "Note" => "Varchar(255)",
    ];

    private static $has_one = [
        "Parent" => Show::class,
        "Character" => Character::class,
        "TeamMember" => TeamMember::class,
        "RoleImage" => Image::class,
    ];

    private static $default_sort = "CharacterID ASC";

    private static $field_labels = [
        "Note" => "Notiz",
        "Character" => "Charakter",
        "TeamMember" => "Teammitglied",
        "RoleImage" => "Bild der Rolle",
    ];

    private static $summary_fields = [
        "Character.Title" => "Charakter",
        "TeamMember.Title" => "Teammitglied",
        "Note" => "Notiz",
        "RoleImage.CMSThumbnail" => "Bild der Rolle",
    ];

    private static $searchable_fields = [
        "Character.Title",
        "TeamMember.Title",
        "Note",
    ];

    private static $owns = [
        "RoleImage",
    ];

    private static $table_name = "ShowCharacter";

    private static $singular_name = "Show Charakter";
    private static $plural_name = "Show Charaktere";

    private static $url_segment = "showcharacter";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('ParentID');
        //Move Notes to the bottom
        $noteField = $fields->dataFieldByName('Note');
        $fields->removeByName('Note');
        $fields->addFieldToTab('Root.Main', $noteField);
        return $fields;
    }

    public function CMSEditLink()
    {
        $admin = ShowAdmin::singleton();
        $urlClass = str_replace('\\', '-', self::class);
        return $admin->Link("/{$urlClass}/EditForm/field/{$urlClass}/showcharacter/{$this->ID}/edit");
    }

    public function RenderRole()
    {
        if($this->Character)
        {
            return $this->Character->Title;
        } else {
            return "Nebenrolle";
        }
    }

    public function RenderTeamMemberName()
    {
        if($this->TeamMember)
        {
            return $this->TeamMember->Title;
        } else {
            if($this->Character)
            {
                if($this->Character->Type == 'humanplayed')
                {
                    return $this->Character->Title . " (Unbekannter Schauspieler)";
                } elseif($this->Character->Type == 'animatronic'){
                    return $this->Character->Title . " (Animatronik)";
                } else {
                    return $this->Character->Title . " (Keine Schauspielerrolle)";
                }
            } else {
                return "Unbekannter Schauspieler";
            }
        }
    }
}
