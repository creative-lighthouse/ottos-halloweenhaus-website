<?php

namespace App\Wiki;

use SilverStripe\Assets\Image;
use App\Wiki\ArtefactOwnership;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Model\List\GroupedList;

/**
 * Class \App\Wiki\Character
 *
 * @property ?string $URLSlug
 * @property ?string $Title
 * @property ?string $Place
 * @property ?string $Jointime
 * @property ?string $Bodysize
 * @property ?string $Bodyweight
 * @property ?string $Age
 * @property ?string $Description
 * @property int $SortField
 * @property ?string $Type
 * @property ?string $ShortDescription
 * @property ?string $GlossaryTerms
 * @property int $ImageID
 * @method Image Image()
 * @method DataList<PhotoGalleryImage> PhotoGalleryImages()
 * @method ManyManyList<WikiMusic> Music()
 * @mixin PhotoGalleryExtension
 * @mixin WikiSlugExtension
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class Character extends DataObject
{
    private static $db = [
        "Title" => "Varchar(255)",
        "Place" => "Varchar(255)",
        "Jointime" => "Varchar(255)",
        "Bodysize" => "Varchar(255)",
        "Bodyweight" => "Varchar(255)",
        "Age" => "Varchar(255)",
        "Description" => "HTMLText",
        "SortField" => "Int",
        "Type" => "Varchar(255)",
        "ShortDescription" => "Varchar(50)",
        "GlossaryTerms" => "Varchar(500)",
    ];

    private static $has_one = [
        "Image" => Image::class,
    ];

    private static $many_many = [
        "Music" => WikiMusic::class,
    ];

    private static $belongs_many = [
        "ShowCharacters" => ShowCharacter::class,
        "MediaProjects" => MediaProject::class,
        "Shows" => Show::class,
    ];

    private static $owns = [
        "Image"
    ];

    private static $default_sort = "SortField ASC";

    private static $field_labels = [
        "Title" => "Name",
        "Place" => "Herkunft",
        "Jointime" => "Erster Auftritt",
        "Bodysize" => "Körpergröße",
        "Bodyweight" => "Körpergewicht",
        "Age" => "Alter",
        "Description" => "Beschreibung",
        "Image" => "Bild",
        "Type" => "Typ",
        "ShortDescription" => "Kurze Beschreibung (Max 50 Zeichen)",
        "GlossaryTerms" => "Glossar-Begriffe (Semikolon-getrennt)",
    ];

    private static $summary_fields = [
        "Title" => "Name",
        "Place" => "Herkunft",
        "Jointime" => "Erster Auftritt",
        "ShortDescription" => "Kurze Beschreibung",
    ];

    private static $searchable_fields = [
        "Title",
        "Description",
    ];

    private static $table_name = "Characters";

    private static $singular_name = "Charakter";
    private static $plural_name = "Charaktere";

    private static $url_segment = "character";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Main', DropdownField::create('Type', 'Typ', [
            'humanplayed' => 'Von Schauspieler gespielt',
            'animatronic' => 'Animatronik',
            'other' => 'Sonstiges'
        ]), 'Description');
        $fields->removeByName("SortField");
        $fields->dataFieldByName('GlossaryTerms')
            ->setDescription('Zusätzliche Begriffe, unter denen dieser Eintrag im Glossar gefunden werden soll. Mehrere Begriffe mit Semikolon trennen, z.B.: "Otto;der Woodmann;Waldgeist"');
        return $fields;
    }

    public function CMSEditLink()
    {
        $admin = WikiAdmin::singleton();
        $urlClass = str_replace('\\', '-', self::class);
        return $admin->Link("/{$urlClass}/EditForm/field/{$urlClass}/character/{$this->ID}/edit");
    }

    public function getLink ()
    {
        $wikiPage = WikiPage::get()->first();
        if($wikiPage)
        {
            return $wikiPage->Link("character/" . ($this->URLSlug ?: $this->ID));
        } else {
            return "";
        }
    }

    public function getGroupedShowCharacters()
    {
        $groupedShowCharacters = GroupedList::create(ShowCharacter::get()->filter('CharacterID', $this->ID));
        return $groupedShowCharacters;
    }

    public function getGroupedArtefactOwnerships()
    {
        $groupedArtefactOwnerships = GroupedList::create(ArtefactOwnership::get()->filter('CharacterID', $this->ID));
        return $groupedArtefactOwnerships;
    }

    public function NextCharacter()
    {
        return Character::get()->filter('SortField:GreaterThan', $this->SortField)->sort('SortField', 'ASC')->first();
    }

    public function PreviousCharacter()
    {
        return Character::get()->filter('SortField:LessThan', $this->SortField)->sort('SortField', 'DESC')->first();
    }
}
