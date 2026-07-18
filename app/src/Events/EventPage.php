<?php

namespace App\Events;

use Page;
use DateTime;
use DateTimeZone;
use App\Events\Event;
use SilverStripe\Assets\Image;
use SilverStripe\AssetAdmin\Forms\UploadField;

/**
 * Class \App\Events\EventPage
 *
 * @property int $HeaderImageID
 * @method Image HeaderImage()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class EventPage extends Page
{
    private static $table_name = 'EventPage';

    private static $has_one = [
        "HeaderImage" => Image::class,
    ];

    private static $owns = [
        "HeaderImage"
    ];

    private static $icon = "app/client/icons/events_admin.svg";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab("Root.Main", UploadField::create("HeaderImage", "Headerbild"));
        return $fields;
    }

    public function hasEvents()
    {
        $now = new DateTime("now", new DateTimeZone("Europe/Berlin"));
        $events = Event::get()->filter([
            "EventDate:GreaterThanOrEqual" => $now->format("Y-m-d"),
            "Visible" => true,
        ]);

        foreach ($events as $event) {
            if ($event->FreeTimeSlotsInFuture()->Count() > 0) {
                return true;
            }
        }

        return false;
    }
}
