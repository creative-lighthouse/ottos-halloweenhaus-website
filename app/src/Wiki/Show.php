<?php

namespace App\Wiki;

use App\Team\TeamMember;
use App\Wiki\Artefact;
use App\Wiki\ShowAdmin;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;
use SilverStripe\Model\List\GroupedList;

/**
 * Class \App\Wiki\Show
 *
 * @property ?string $URLSlug
 * @property int $Year
 * @property ?string $Title
 * @property ?string $Place
 * @property ?string $Storyline
 * @property ?string $WalkthroughLink
 * @property ?string $GuestCount
 * @property ?string $DaysOpen
 * @property ?string $WalkingLength
 * @property ?string $ShowLength
 * @property ?string $TeamSize
 * @property ?string $SceneCount
 * @property ?string $StatisticsNote
 * @property ?string $GlossaryTerms
 * @property int $PosterImageID
 * @property int $ShowImageID
 * @method Image PosterImage()
 * @method Image ShowImage()
 * @method DataList<PhotoGalleryImage> PhotoGalleryImages()
 * @method DataList<ShowCharacter> ShowCharacters()
 * @method ManyManyList<TeamMember> BackstageHelpers()
 * @method ManyManyList<WikiMusic> Music()
 * @method ManyManyList<Location> Locations()
 * @method ManyManyList<Artefact> Artefacts()
 * @mixin PhotoGalleryExtension
 * @mixin WikiSlugExtension
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class Show extends DataObject
{
    private static $db = [
        "Year" => "Int",
        "Title" => "Varchar(255)",
        "Place" => "Varchar(255)",
        "Storyline" => "HTMLText",
        "WalkthroughLink" => "Varchar(255)",
        "GuestCount" => "Varchar(255)",
        "DaysOpen" => "Varchar(255)",
        "WalkingLength" => "Varchar(255)",
        "ShowLength" => "Varchar(255)",
        "TeamSize" => "Varchar(255)",
        "SceneCount" => "Varchar(255)",
        "StatisticsNote" => "Varchar(255)",
        "GlossaryTerms" => "Varchar(500)",
    ];

    private static $has_one = [
        "PosterImage" => Image::class,
        "ShowImage" => Image::class,
    ];

    private static $has_many = [
        "ShowCharacters" => ShowCharacter::class,
    ];

    private static $many_many = [
        "BackstageHelpers" => TeamMember::class,
        "Music" => WikiMusic::class,
    ];

    private static $belongs_many_many = [
        "Locations" => Location::class,
        "Artefacts" => Artefact::class,
    ];

    private static $owns = [
        "PosterImage",
        "ShowImage",
        "ShowCharacters",
    ];

    private static $default_sort = "Year DESC";

    private static $field_labels = [
        "Year" => "Jahr",
        "Title" => "Titel",
        "Place" => "Ort",
        "Storyline" => "Handlung",
        "PosterImage" => "Poster Bild",
        "ShowImage" => "Show Bild",
        "WalkthroughLink" => "Walkthrough Video-Link (Youtube)",
        "GuestCount" => "Gästeanzahl",
        "DaysOpen" => "Tage geöffnet",
        "WalkingLength" => "Laufstrecke (in Metern)",
        "ShowLength" => "Showlänge (in Minuten)",
        "TeamSize" => "Teamgröße",
        "SceneCount" => "Anzahl der Szenen",
        "ShowCharacters" => "Charaktere",
        "Locations" => "Orte",
        "Artefacts" => "Artefakte",
        "BackstageHelpers" => "Hintergrundhelfer",
        "GlossaryTerms" => "Glossar-Begriffe (Semikolon-getrennt)",
    ];

    private static $summary_fields = [
        "Year" => "Jahr",
        "Title" => "Titel",
        "Place" => "Ort",
    ];

    private static $searchable_fields = [
        "Year",
        "Title",
        "Place",
    ];

    private static $table_name = "Show";

    private static $singular_name = "Show";
    private static $plural_name = "Shows";

    private static $url_segment = "show";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        //Media Tab
        $fields->findOrMakeTab('Root.Media', 'Medien');
        $fields->addFieldToTab('Root.Media', $fields->dataFieldByName('PosterImage'));
        $fields->addFieldToTab('Root.Media', $fields->dataFieldByName('ShowImage'));
        $fields->addFieldToTab('Root.Media', $fields->dataFieldByName('WalkthroughLink'));

        //Statistics Tab
        $fields->findOrMakeTab('Root.Statistics', 'Statistiken');
        $fields->addFieldToTab('Root.Statistics', $fields->dataFieldByName('GuestCount'));
        $fields->addFieldToTab('Root.Statistics', $fields->dataFieldByName('DaysOpen'));
        $fields->addFieldToTab('Root.Statistics', $fields->dataFieldByName('WalkingLength'));
        $fields->addFieldToTab('Root.Statistics', $fields->dataFieldByName('ShowLength'));
        $fields->addFieldToTab('Root.Statistics', $fields->dataFieldByName('TeamSize'));
        $fields->addFieldToTab('Root.Statistics', $fields->dataFieldByName('SceneCount'));
        $fields->addFieldToTab('Root.Statistics', $fields->dataFieldByName('StatisticsNote'));
        $fields->dataFieldByName('GlossaryTerms')
            ->setDescription('Zusätzliche Begriffe, unter denen diese Show im Glossar gefunden werden soll. Mehrere Begriffe mit Semikolon trennen, z.B.: "Die Show 2019;Geisterhaus 2019"');
        return $fields;
    }

    public function CMSEditLink()
    {
        $admin = WikiAdmin::singleton();
        $urlClass = str_replace('\\', '-', self::class);
        return $admin->Link("/{$urlClass}/EditForm/field/{$urlClass}/show/{$this->ID}/edit");
    }

    public function getGroupedCharacters()
    {
        //Group the Characters because a character can be played by multiple TeamMembers
        $groupedCharacters = GroupedList::create($this->ShowCharacters());
        return $groupedCharacters;
    }

    public function getLink ()
    {
        $wikiPage = WikiPage::get()->first();
        if($wikiPage)
        {
            return $wikiPage->Link("show/" . ($this->URLSlug ?: $this->ID));
        } else {
            return "";
        }
    }

    public function getNextShow()
    {
        //Check if there is a next show
        $nextShow = Show::get()->filter('Year:GreaterThan', $this->Year)->sort('Year', 'ASC')->first();
        return $nextShow;
    }

    public function getPrevShow()
    {
        //Check if there is a previous show
        $prevShow = Show::get()->filter('Year:LessThan', $this->Year)->sort('Year', 'DESC')->first();
        return $prevShow;
    }
}
