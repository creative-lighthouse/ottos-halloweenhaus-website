<?php

namespace App\Elements;

use App\Events\Event;
use DNADesign\Elemental\Models\BaseElement;

/**
 * Class \App\Elements\TimelineElement
 *
 * @property ?string $Content
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class EventsElement extends BaseElement
{

    private static $db = [
        "Content" => "HTMLText",
    ];

    private static $table_name = 'EventsElement';
    private static $icon = 'sp-icon-calendar-element';

    public function getType()
    {
        return _t(__CLASS__ . '.BlockType', 'Eventliste');
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        return $fields;
    }

    public function getEvents()
    {
        return Event::get()->filter("StartTime:GreaterThan", date("Y-m-d H:i:s"))->sort("StartTime ASC");
    }
}
