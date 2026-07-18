<?php

namespace App\Wiki;

use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;

/**
 * Class \App\Wiki\Artefact
 *
 * @property ?string $URLSlug
 * @property ?string $Title
 * @property ?string $Jointime
 * @property ?string $Description
 * @property ?string $ShortDescription
 * @property int $SortField
 * @property ?string $GlossaryTerms
 * @property int $ImageID
 * @method Image Image()
 * @method DataList<PhotoGalleryImage> PhotoGalleryImages()
 * @method DataList<ArtefactOwnership> ArtefactOwnerships()
 * @method ManyManyList<Show> Shows()
 * @method ManyManyList<MediaProject> MediaProjects()
 * @method ManyManyList<WikiMusic> Music()
 * @mixin PhotoGalleryExtension
 * @mixin WikiSlugExtension
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class Artefact extends DataObject
{
    private static $db = [
        "Title" => "Varchar(255)",
        "Jointime" => "Varchar(255)",
        "Description" => "HTMLText",
        "ShortDescription" => "Varchar(50)",
        "SortField" => "Int",
        "GlossaryTerms" => "Varchar(500)",
    ];

    private static $many_many = [
        "Shows" => Show::class,
        "MediaProjects" => MediaProject::class,
        "Music" => WikiMusic::class,
    ];

    private static $has_one = [
        "Image" => Image::class,
    ];

    private static $has_many = [
        "ArtefactOwnerships" => ArtefactOwnership::class,
    ];

    private static $owns = [
        "Image",
        "ArtefactOwnerships"
    ];

    private static $default_sort = "SortField ASC";

    private static $field_labels = [
        "Title" => "Name",
        "Jointime" => "Erstes Vorkommen",
        "Description" => "Beschreibung",
        "Image" => "Bild",
        "ShortDescription" => "Kurze Beschreibung (Max 50 Zeichen)",
        "Shows" => "Shows",
        "ArtefactOwnerships" => "Besitzer",
        "GlossaryTerms" => "Glossar-Begriffe (Semikolon-getrennt)",
    ];

    private static $summary_fields = [
        "Title" => "Name",
        "Jointime" => "Erstes Vorkommen",
    ];

    private static $searchable_fields = [
        "Title",
        "Jointime",
    ];

    private static $table_name = "Artefacts";

    private static $singular_name = "Artefakt";
    private static $plural_name = "Artefakte";

    private static $url_segment = "artefact";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        //$fields->removeByName("SortField");
        $fields->dataFieldByName('GlossaryTerms')
            ->setDescription('Zusätzliche Begriffe, unter denen dieses Artefakt im Glossar gefunden werden soll. Mehrere Begriffe mit Semikolon trennen, z.B.: "Das Schwert;Ottos Klinge"');
        return $fields;
    }

    public function CMSEditLink()
    {
        $admin = WikiAdmin::singleton();
        $urlClass = str_replace('\\', '-', self::class);
        return $admin->Link("/{$urlClass}/EditForm/field/{$urlClass}/artefact/{$this->ID}/edit");
    }

    public function getLink ()
    {
        $wikiPage = WikiPage::get()->first();
        if($wikiPage)
        {
            return $wikiPage->Link("artefact/" . ($this->URLSlug ?: $this->ID));
        } else {
            return "";
        }
    }

    public function getArtefactShows()
    {
        return $this->Shows()->sort("Year DESC");
    }

    public function getOwners()
    {
        return $this->ArtefactOwnerships()->sort("StartTime DESC");
    }

    public function NextArtefact()
    {
        return Artefact::get()->filter('SortField:GreaterThan', $this->SortField)->sort('SortField', 'ASC')->first();
    }

    public function PreviousArtefact()
    {
        return Artefact::get()->filter('SortField:LessThan', $this->SortField)->sort('SortField', 'DESC')->first();
    }
}
