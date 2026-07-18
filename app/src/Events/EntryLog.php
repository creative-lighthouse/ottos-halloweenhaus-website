<?php

namespace App\Events;

use SilverStripe\ORM\DataObject;

/**
 * Class \App\Events\EntryLog
 *
 * @property ?string $EntryTime
 * @property int $SQ
 * @property int $VQ
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class EntryLog extends DataObject
{
    private static $db = [
        "EntryTime" => "Datetime",
        "SQ" => "Int",
        "VQ" => "Int",
    ];

    private static $default_sort = "EntryTime DESC";

    private static $field_labels = [
        "EntryTime" => "Eintrittszeit",
        "VQ" => "Virtual Queue Gäste",
        "SQ" => "Standby Queue Gäste",
    ];

    private static $summary_fields = [
        "EntryTime" => "Eintrittszeit",
        "VQ" => "Virtual Queue Gäste",
        "SQ" => "Standby Queue Gäste",
        "TotalGuests" => "Gesamtanzahl",
    ];

    private static $table_name = "EntryLog";

    private static $singular_name = "Eintrittslog";
    private static $plural_name = "Eintrittslogs";

    private static $url_segment = "entrylog";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        return $fields;
    }

    public function getTotalGuests()
    {
        return $this->SQ + $this->VQ;
    }

    public function getDay()
    {
        return date("d.m.Y", strtotime($this->EntryTime));
    }

    public function getTotalGuestCountOnSameDay()
    {
        $entryTime = date("Y-m-d", strtotime($this->EntryTime));
        $entryLogs = EntryLog::get()->filter([
            "EntryTime:GreaterThanOrEqual" => date("Y-m-d H:i:s", strtotime("midnight", strtotime($entryTime))),
            "EntryTime:LessThanOrEqual" => date("Y-m-d H:i:s", strtotime("tomorrow", strtotime($entryTime))),
        ]);
        $totalGuestCount = 0;
        foreach ($entryLogs as $entryLog) {
            $totalGuestCount += $entryLog->getTotalGuests();
        }
        return $totalGuestCount;
    }

    public function getVQGuestCountOnSameDay()
    {
        $entryTime = date("Y-m-d", strtotime($this->EntryTime));
        $entryLogs = EntryLog::get()->filter([
            "EntryTime:GreaterThanOrEqual" => date("Y-m-d H:i:s", strtotime("midnight", strtotime($entryTime))),
            "EntryTime:LessThanOrEqual" => date("Y-m-d H:i:s", strtotime("tomorrow", strtotime($entryTime))),
        ]);
        $vqGuestCount = 0;
        foreach ($entryLogs as $entryLog) {
            $vqGuestCount += $entryLog->VQ;
        }
        return $vqGuestCount;
    }

    public function getSQGuestCountOnSameDay()
    {
        $entryTime = date("Y-m-d", strtotime($this->EntryTime));
        $entryLogs = EntryLog::get()->filter([
            "EntryTime:GreaterThanOrEqual" => date("Y-m-d H:i:s", strtotime("midnight", strtotime($entryTime))),
            "EntryTime:LessThanOrEqual" => date("Y-m-d H:i:s", strtotime("tomorrow", strtotime($entryTime))),
        ]);
        $sqGuestCount = 0;
        foreach ($entryLogs as $entryLog) {
            $sqGuestCount += $entryLog->SQ;
        }
        return $sqGuestCount;
    }
}
