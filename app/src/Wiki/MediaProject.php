<?php

namespace App\Wiki;

use App\Team\TeamMember;
use SilverStripe\Assets\Image;
use App\Wiki\ArtefactOwnership;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\DropdownField;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\Model\List\GroupedList;

/**
 * Class \App\Wiki\MediaProject
 *
 * @property ?string $URLSlug
 * @property ?string $Title
 * @property ?string $Place
 * @property ?string $Description
 * @property ?string $PublicationDate
 * @property ?string $VideoLink
 * @property int $SortField
 * @property ?string $GlossaryTerms
 * @property int $ImageID
 * @method Image Image()
 * @method DataList<Link> Links()
 * @method ManyManyList<TeamMember> TeamMembers()
 * @method ManyManyList<WikiMusic> Music()
 * @method ManyManyList<Location> Locations()
 * @method ManyManyList<Artefact> Artefacts()
 * @mixin WikiSlugExtension
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class MediaProject extends DataObject
{
    private static $db = [
        "Title" => "Varchar(255)",
        "Place" => "Varchar(255)",
        "Description" => "HTMLText",
        "PublicationDate" => "Date",
        "VideoLink" => "Varchar(255)",
        "SortField" => "Int",
        "GlossaryTerms" => "Varchar(500)",
    ];

    private static $has_one = [
        "Image" => Image::class,
    ];

    private static $has_many = [
        "Links" => Link::class,
    ];

    private static $owns = [
        "Image",
        "Links",
    ];

    private static $many_many = [
        "TeamMembers" => TeamMember::class,
        "Music" => WikiMusic::class,
    ];

    private static $belongs_many_many = [
        "Locations" => Location::class,
        "Artefacts" => Artefact::class,
    ];

    private static $default_sort = "PublicationDate DESC";

    private static $field_labels = [
        "Title" => "Titel",
        "Place" => "Veröffentlichungsort",
        "Description" => "Beschreibung",
        "PublicationDate" => "Veröffentlichungsdatum",
        "VideoLink" => "Video-Link",
        "TeamMembers" => "Teammitglieder",
        "GlossaryTerms" => "Glossar-Begriffe (Semikolon-getrennt)",
    ];

    private static $summary_fields = [
        "Title" => "Titel",
        "RenderPublicationDate" => "Veröffentlichungsdatum",
    ];

    private static $searchable_fields = [
        "Title",
        "Place",
        "PublicationDate",
    ];

    private static $table_name = "MediaProjects";

    private static $singular_name = "Medienprojekt";
    private static $plural_name = "Medienprojekte";

    private static $url_segment = "mediaproject";

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
            return $wikiPage->Link("media/" . ($this->URLSlug ?: $this->ID));
        } else {
            return "";
        }
    }

    public function NextMediaProject()
    {
        return MediaProject::get()->filter('PublicationDate:GreaterThan', $this->PublicationDate)->sort('PublicationDate', 'ASC')->first();
    }

    public function PreviousMediaProject()
    {
        return MediaProject::get()->filter('PublicationDate:LessThan', $this->PublicationDate)->sort('PublicationDate', 'DESC')->first();
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
