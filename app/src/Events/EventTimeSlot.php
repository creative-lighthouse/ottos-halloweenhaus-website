<?php

namespace App\Events;

use App\Events\Registration;
use SilverStripe\ORM\DataObject;

/**
 * Class \App\Events\EventTimeSlot
 *
 * @property ?string $SlotTime
 * @property int $MaxAttendees
 * @property int $MaxVIPs
 * @property bool $Active
 * @property int $ParentID
 * @method Event Parent()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class EventTimeSlot extends DataObject
{
    private static $db = [
        "SlotTime" => "Time",
        "MaxAttendees" => "Int",
        "MaxVIPs" => "Int",
        "Active" => "Boolean",
    ];

    private static $has_one = [
        "Parent" => Event::class,
    ];

    private static $belongs_many = [
        "Registrations" => Registration::class,
    ];

    private static $default_sort = "SlotTime ASC";

    private static $field_labels = [
        "SlotTime" => "Startzeit",
        "MaxAttendees" => "Maximale Anzahl an Teilnehmern",
        "MaxVIPs" => "Maximale Anzahl an VIPs",
        "Active" => "Aktiv",
    ];

    private static $summary_fields = [
        "SlotTime" => "Startzeit",
        "AttendeesFormatted" => "Teilnehmer",
        "CouponAttendeesFormatted" => "VIPs",
        "Active" => "Aktiv",
    ];

    private static $searchable_fields = [
        "SlotTime",
        "MaxAttendees",
        "MaxVIPs",
        "Active",
    ];

    private static $table_name = "EventTimeSlot";

    private static $singular_name = "Zeit-Slot";
    private static $plural_name = "Zeit-Slots";

    private static $url_segment = "timeslot";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName("ParentID");
        return $fields;
    }

    public function getRegistrationsCount()
    {
        $allRegistrations = Registration::get()->filter(["TimeSlotID" => $this->ID, "UsedCouponID" => 0]);
        $registrationCount = 0;
        foreach ($allRegistrations as $registration) {
            if ($registration->Event()->ID == $this->ParentID) {
                $registrationCount += $registration->GroupSize;
            }
        }
        return $registrationCount;
    }

    public function getCouponRegistrationsCount()
    {
        $allRegistrations = Registration::get()->filter(["TimeSlotID" => $this->ID, "UsedCouponID:not" => 0]);
        $registrationCount = 0;
        foreach ($allRegistrations as $registration) {
            if ($registration->Event()->ID == $this->ParentID) {
                $registrationCount += $registration->GroupSize;
            }
        }
        return $registrationCount;
    }

    public function AttendeesFormatted()
    {
        $places = $this->MaxAttendees - $this->getRegistrationsCount();
        switch ($places) {
            case 0:
                return "Ausgebucht";
            case 1:
                return "1 Platz frei";
            default:
                return $places . " Plätze frei";
        }
    }

    public function CouponAttendeesFormatted()
    {
        $places = $this->MaxVIPs - $this->getCouponRegistrationsCount();
        switch ($places) {
            case 0:
                return "Ausgebucht";
            case 1:
                return "1 Platz frei";
            default:
                return $places . " Plätze frei";
        }
    }

    public function getFreeSlotCount()
    {
        return $this->MaxAttendees - $this->getRegistrationsCount();
    }

    public function getFreeCouponSlotCount()
    {
        return $this->MaxVIPs - $this->getCouponRegistrationsCount();
    }

    public function getSlotTimeFormatted()
    {
        return date("H:i", strtotime($this->SlotTime));
    }

    public function getSlotTimeEndFormatted()
    {
        $slotDuration = $this->Parent()->SlotDuration;
        $slotTime = strtotime($this->SlotTime);
        $slotTimeEnd = $slotTime + $slotDuration * 60;
        return date("H:i", $slotTimeEnd);
    }

    public function getTitle()
    {
        return $this->getSlotTimeFormatted() . " - " . $this->getSlotTimeEndFormatted();
    }
}
