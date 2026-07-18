<?php

namespace App\Wiki;

use App\Wiki\Artefact;
use App\Wiki\Character;
use App\Wiki\Location;
use App\Wiki\MediaProject;
use SilverStripe\Assets\File;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\DataObject;

/**
 * Class \App\Wiki\WikiMusic
 *
 * @property ?string $URLSlug
 * @property ?string $Title
 * @property ?string $PublicationDate
 * @property ?string $Composer
 * @property ?string $ShortDescription
 * @property ?string $Description
 * @property ?string $MusicVideoLink
 * @property ?string $GlossaryTerms
 * @property int $SoundFileID
 * @method File SoundFile()
 * @method DataList<Link> Links()
 * @method ManyManyList<Location> Locations()
 * @method ManyManyList<Artefact> Artefacts()
 * @method ManyManyList<Character> Characters()
 * @method ManyManyList<MediaProject> MediaProjects()
 * @mixin WikiSlugExtension
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class WikiMusic extends DataObject
{
    private static $db = [
        "Title" => "Varchar(255)",
        "PublicationDate" => "Date",
        "Composer" => "Varchar(255)",
        "ShortDescription" => "Varchar(255)",
        "Description" => "HTMLText",
        "MusicVideoLink" => "Varchar(255)",
        "GlossaryTerms" => "Varchar(500)",
    ];

    private static $has_one = [
        "SoundFile" => File::class,
    ];

    private static $has_many = [
        "Links" => Link::class,
    ];

    private static $owns = [
        "SoundFile",
        "Links",
    ];

    private static $belongs_many_many = [
        "Locations" => Location::class,
        "Artefacts" => Artefact::class,
        "Characters" => Character::class,
        "MediaProjects" => MediaProject::class,
    ];

    private static $default_sort = "PublicationDate DESC, Title ASC";

    private static $field_labels = [
        "Title" => "Titel",
        "Description" => "Beschreibung",
        "PublicationDate" => "Veröffentlichungsdatum",
        "MusicEmbed" => "Musik-Einbettung",
        "Author" => "Autor",
        "GlossaryTerms" => "Glossarbegriffe",
        "SoundFile" => "Musikdatei",
        "Locations" => "Orte",
        "Artefacts" => "Artefakte",
        "Characters" => "Charaktere",
        "MediaProjects" => "Medienprojekte",
    ];

    private static $summary_fields = [
        "Title" => "Titel",
        "RenderPublicationDate" => "Veröffentlichungsdatum",
    ];

    private static $searchable_fields = [
        "Title",
        "PublicationDate",
        "GlossaryTerms",
    ];

    private static $table_name = "WikiMusic";

    private static $singular_name = "Musikstück";
    private static $plural_name = "Musikstücke";

    private static $url_segment = "music";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName("SortField");
        $fields->dataFieldByName('GlossaryTerms')
            ->setDescription('Zusätzliche Begriffe, unter denen dieses Medienprojekt im Glossar gefunden werden soll. Mehrere Begriffe mit Semikolon trennen, z.B.: "Das Video;Halloweenfilm 2023"');
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
            return $wikiPage->Link("music/" . ($this->URLSlug ?: $this->ID));
        } else {
            return "";
        }
    }

    public function NextMusic()
    {
        return WikiMusic::get()->filter('PublicationDate:GreaterThan', $this->PublicationDate)->sort('PublicationDate', 'ASC')->first();
    }

    public function PrevMusic()
    {
        return WikiMusic::get()->filter('PublicationDate:LessThan', $this->PublicationDate)->sort('PublicationDate', 'DESC')->first();
    }

    public function getRenderPublicationDate()
    {
        if($this->PublicationDate)
        {
            return date('d.m.Y', strtotime($this->PublicationDate));
        }
        return "";
    }
}
