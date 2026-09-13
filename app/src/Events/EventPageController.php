<?php

namespace App\Events;

use PageController;
use App\Events\Event;
use App\Events\Registration;
use SilverStripe\ORM\DB;
use SilverStripe\Forms\Form;
use App\Feedback\FeedbackPage;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Model\List\GroupedList;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Security\SecurityToken;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;

/**
 * Class \App\Events\EventPageController
 *
 * @property EventPage $dataRecord
 * @method EventPage data()
 * @mixin EventPage
 */
class EventPageController extends PageController
{

    protected function init()
    {
        parent::init();
        HTTPCacheControlMiddleware::singleton()->disableCache();
    }

    private static $allowed_actions = [
        "view",
        "register",
        "completeregistration",
        "RegistrationForm",
        "registrationsuccessful",
        "registrationfull",
        "registrationconfirm",
        "couponinvalid",
        "eventnotfound",
        "unsubscribe",
        "unsubscribesuccessful",
        "ticket",
        "ticketdata",
        "validateticket",
        "checkcoupon",
        "eventscapacity",
        "error"
    ];

    public function index(HTTPRequest $request)
    {
        return array(
            "UsesCoupon" => $request->getVar("coupon"),
            "PreselectEventID" => null,
        );
    }

    public function view(HTTPRequest $request)
    {
        $id = $this->getRequest()->param("ID");
        $article = Event::get()->byId($id);
        return array(
            "Event" => $article,
        );
    }

    public function register(HTTPRequest $request)
    {
        $id = (int) $this->getRequest()->param("ID");
        $event = Event::get()->byId($id);

        // The dedicated register template is gone: the Vue navigator now handles the
        // whole flow. We just hand it the pre-selected event so it can jump straight
        // to the timeslot step.
        return array(
            "UsesCoupon" => $request->getVar("coupon"),
            "PreselectEventID" => $event ? $event->ID : null,
        );
    }

    public function checkCoupon(HTTPRequest $request)
    {
        $hash = $this->getRequest()->param("ID");

        $json = array();
        $json["Valid"] = false;
        $json["Message"] = "Ungültiger Code";

        $coupon = EventCoupon::get()->filter("Hash", $hash)->First();

        if ($coupon) {
            if ($coupon->MaxUses > 0 && $coupon->UsedCount >= $coupon->MaxUses) {
                $json["Valid"] = false;
                if ($coupon->MaxUses == 1) {
                    $json["Message"] = "Dieser Code wurde bereits verwendet";
                } else {
                    $json["Message"] = "Dieser Code wurde bereits zu oft verwendet";
                }
            } else {
                $json["Valid"] = true;
                $json["Message"] = "Code gültig";
                $json["Title"] = $coupon->Title;
                $json["Type"] = $coupon->Type;
                $json["Code"] = $coupon->Hash;
                $json["Description"] = $coupon->Description;
            }
        }

        $this->response->addHeader('Content-Type', 'application/json');
        return json_encode($json);
    }

    public function RegistrationForm()
    {
        $id = $this->getRequest()->param("ID");

        $fields = FieldList::create(
            HiddenField::create("EventID", "EventID", $id),
            HiddenField::create("TimeSlotID", "TimeSlotID"),
            HiddenField::create("GroupSize", "Gruppengröße"),
            HiddenField::create("Couponcode", "Couponcode"),
            TextField::create("Title", "Vor- & Nachname"),
            EmailField::create("Email", "E-Mail-Adresse"),
            NumericField::create("PLZ", "Postleitzahl (optional)")->setHTML5(true),
            LiteralField::create("DataPrivacyinfo", "Ich habe die <a href='impressum-und-datenschutz'>Datenschutzerklärung</a> gelesen und willige ein, dass meine Daten im Sinne der DSGVO verwendet werden."),
            CheckboxField::create("DataPrivacy", "Datenschutzerklärung akzeptieren"),
        );

        $actions = FieldList::create(
            FormAction::create("completeregistration")->setTitle("Absenden")
        );

        $required = RequiredFieldsValidator::create(
            "Title",
            "EventID",
            "TimeSlotID",
            "GroupSize",
            "Title",
            "Email",
            "DataPrivacy"
        );

        $form = Form::create($this, "RegistrationForm", $fields, $actions, $required);

        return $form;
    }

    public function completeregistration($data, $form)
    {
        $event = Event::get()->byId($data["EventID"]);
        $timeslot = EventTimeSlot::get()->byId($data["TimeSlotID"]);
        $groupsize = $data["GroupSize"];
        $zip = $data["PLZ"] ?? null;
        $couponcode = $data["Couponcode"] ?? null;

        $result = null;

        DB::get_conn()->withTransaction(function () use (
            $event,
            $timeslot,
            $groupsize,
            $zip,
            $couponcode,
            $data,
            &$result
        ) {
            // Locks the timeslot row so concurrent bookings for the same slot are
            // serialized instead of racing past the capacity check below.
            DB::query('SELECT "ID" FROM "EventTimeSlot" WHERE "ID" = ' . (int) $timeslot->ID . ' FOR UPDATE');

            $registrations = Registration::get()->filter([
                "EventID" => $event->ID,
                "TimeSlotID" => $timeslot->ID,
                "UsedCouponID" => 0,
            ]);
            $timeslotRegistrationCount = 0;
            foreach ($registrations as $registration) {
                $timeslotRegistrationCount += $registration->GroupSize;
            }

            $vipRegistrations = Registration::get()->filter([
                "EventID" => $event->ID,
                "TimeSlotID" => $timeslot->ID,
                "UsedCouponID:not" => 0,
            ]);
            $timeslotVIPRegistrationCount = 0;
            foreach ($vipRegistrations as $registration) {
                $timeslotVIPRegistrationCount += $registration->GroupSize;
            }

            if ($couponcode) {
                $coupon = EventCoupon::get()->filter("Hash", $couponcode)->First();
                if (!$coupon) {
                    $result = $this->redirect($this->Link("couponinvalid/$event->ID"));
                    return;
                }

                if (($timeslotVIPRegistrationCount + $groupsize) > $timeslot->MaxVIPs) {
                    $result = $this->redirect($this->Link("registrationfull/$event->ID/$timeslot->ID"));
                    return;
                }

                $registration = Registration::create();
                $registration->EventID = $event->ID;
                $registration->TimeSlotID = $timeslot->ID;
                $registration->GroupSize = $groupsize;
                $registration->ZIP = $zip;
                $registration->Title = $data["Title"];
                $registration->Email = $data["Email"];
                $registration->UsedCouponID = $coupon->ID;
                $registration->Hash = md5($data["Email"] . $event->ID . $timeslot->ID . $groupsize . date("Y-m-d H:i:s"));
                $registration->Status = "Registered";

                $coupon->UsedCount++;
                $coupon->write();

                $registration->write();

                $result = $this->redirect($this->Link("registrationsuccessful/$event->ID/$registration->Hash"));
                return;
            }

            if (($timeslotRegistrationCount + $groupsize) > $timeslot->MaxAttendees) {
                $result = $this->redirect($this->Link("registrationfull/$event->ID/$timeslot->ID"));
                return;
            }

            $registration = Registration::create();
            $registration->EventID = $event->ID;
            $registration->TimeSlotID = $timeslot->ID;
            $registration->GroupSize = $groupsize;
            $registration->ZIP = $zip;
            $registration->Title = $data["Title"];
            $registration->Email = $data["Email"];
            $registration->Hash = md5($data["Email"] . $event->ID . $timeslot->ID . $groupsize . date("Y-m-d H:i:s"));
            $registration->Status = "Registered";
            $registration->write();

            $result = $this->redirect($this->Link("registrationsuccessful/$event->ID/$registration->Hash"));
        });

        return $result;
    }

    public function registrationsuccessful(HTTPRequest $request)
    {
        $event_id = $request->param("ID");
        $event = Event::get()->byId($event_id);
        $hash = $request->param("OtherID");

        if ($hash != "" && isset($event)) {
            $registration = Registration::get()->filter(array("Hash" => $hash))->First();
            if ($registration) {
                return array(
                    "Event" => $event,
                    "Registration" => $registration,
                );
            }
        } else {
            return $this->redirect($this->Link("error") . "?error=Unbekannte Registrierung");
        }
    }

    public function registrationfull(HTTPRequest $request)
    {
        $event_id = $request->param("ID");
        $timeslot_id = $request->param("OtherID");
        $event = Event::get()->byId($event_id);
        $timeslot = EventTimeSlot::get()->byId($timeslot_id);

        if (isset($event) && isset($timeslot)) {
            return array(
                "Event" => $event,
                "TimeSlot" => $timeslot,
            );
        } else {
            return $this->redirect($this->Link("eventnotfound"));
        }
    }

    public function registrationconfirm(HTTPRequest $request)
    {
        $event_id = $_GET["event"];
        $event = Event::get()->byId($event_id);

        if (!isset($_GET["hash"])) {
            return $this->redirect($this->Link("error") . "?error=Unbekannte Registrierung");
        } else {
            $hash = $_GET["hash"];
        }
        if (!isset($event)) {
            return $this->redirect($this->Link("error") . "?error=Unbekanntes Event");
        }

        $registration = Registration::get()->filter(array(
            "Hash" => $hash,
            "EventID" => $event_id,
        ))->First();

        if ($registration) {
            if ($registration->Status != "Registered") {
                return $this->redirect($this->Link("ticket/" . $registration->Hash));
            }

            if ($registration->ConfirmSecurityID) {
                $securityid = $_GET["securityid"] ?? null;
                if ($securityid !== $registration->ConfirmSecurityID) {
                    return $this->redirect($this->Link("error") . "?error=Sicherheitscode ist ungültig");
                }
            }

            $registration->Status = "Confirmed";
            $registration->write();
            return array(
                "Event" => $event,
                "Registration" => $registration,
            );
        } else {
            return $this->redirect($this->Link("error") . "?error=Hash passt zu keiner Registrierung");
        }
    }

    public function error(HTTPRequest $request)
    {
        $errormessage = $_GET["error"];
        return [
            "ErrorMessage" => $errormessage
        ];
    }

    public function getEvents()
    {
        return Event::get()->filter("EventDate:GreaterThanOrEqual", date("Y-m-d H:i:s"))->filter("Visible", true)->sort("EventDate ASC, StartTime ASC");
    }

    public function getGroupedEvents()
    {
        $events = $this->getEvents();
        return GroupedList::create($events);
    }

    /**
     * JSON payload the Vue events navigator boots from. Rendered into the page as
     * <script type="application/json" id="events-initial-data">.
     */
    public function getEventsDataJSON()
    {
        return json_encode(
            $this->buildEventsData(),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    }

    /**
     * Lightweight capacity-only feed the Vue navigator polls so timeslots that
     * fill up while someone is booking update without a page reload.
     */
    public function eventscapacity(HTTPRequest $request)
    {
        $this->response->addHeader('Content-Type', 'application/json');
        return json_encode($this->buildEventsData(true));
    }

    /**
     * Single source of truth for both the initial navigator payload and the
     * capacity poll. With $capacityOnly the return is trimmed to per-timeslot
     * availability keyed by timeslot ID.
     */
    private function buildEventsData(bool $capacityOnly = false): array
    {
        $mapSlot = function (EventTimeSlot $slot, bool $isFull, bool $coupon): array {
            return [
                "id" => (int) $slot->ID,
                "time" => $slot->getSlotTimeFormatted(),
                "endTime" => $slot->getSlotTimeEndFormatted(),
                "freeCount" => $coupon
                    ? (int) $slot->getFreeCouponSlotCount()
                    : (int) $slot->getFreeSlotCount(),
                "totalCount" => $coupon ? (int) $slot->MaxVIPs : (int) $slot->MaxAttendees,
                "full" => $isFull,
                "capacityLabel" => $isFull
                    ? "Ausgebucht"
                    : ($coupon ? $slot->CouponAttendeesFormatted() : $slot->AttendeesFormatted()),
            ];
        };

        $dates = [];
        $timeslotCapacity = [];
        $couponTimeslotCapacity = [];

        foreach ($this->getEvents() as $event) {
            $freeSlots = $event->FreeTimeSlotsInFuture();
            $fullSlots = $event->FullTimeSlots();
            $freeCouponSlots = $event->FreeCouponTimeSlotsInFuture();
            $fullCouponSlots = $event->FullCouponTimeSlots();

            $timeslots = [];
            foreach ($freeSlots as $slot) {
                $timeslots[] = $mapSlot($slot, false, false);
            }
            foreach ($fullSlots as $slot) {
                $timeslots[] = $mapSlot($slot, true, false);
            }

            $couponTimeslots = [];
            foreach ($freeCouponSlots as $slot) {
                $couponTimeslots[] = $mapSlot($slot, false, true);
            }
            foreach ($fullCouponSlots as $slot) {
                $couponTimeslots[] = $mapSlot($slot, true, true);
            }

            if ($capacityOnly) {
                foreach ($timeslots as $slot) {
                    $timeslotCapacity[$slot["id"]] = [
                        "freeCount" => $slot["freeCount"],
                        "totalCount" => $slot["totalCount"],
                        "full" => $slot["full"],
                        "capacityLabel" => $slot["capacityLabel"],
                    ];
                }
                foreach ($couponTimeslots as $slot) {
                    $couponTimeslotCapacity[$slot["id"]] = [
                        "freeCount" => $slot["freeCount"],
                        "totalCount" => $slot["totalCount"],
                        "full" => $slot["full"],
                        "capacityLabel" => $slot["capacityLabel"],
                    ];
                }
                continue;
            }

            $dateKey = $event->EventDate;
            if (!isset($dates[$dateKey])) {
                $dates[$dateKey] = [
                    "date" => $dateKey,
                    "weekday" => $event->getDateWeekday(),
                    "day" => $event->getDateDay(),
                    "month" => $event->getDateMonthTitle(),
                    "events" => [],
                ];
            }

            $dates[$dateKey]["events"][] = [
                "id" => (int) $event->ID,
                "title" => $event->Title,
                "image" => $event->Image()->exists()
                    ? $event->Image()->FocusFill(500, 200)->URL
                    : null,
                "slotDuration" => (int) $event->SlotDuration,
                "date" => $dateKey,
                "timeslots" => $timeslots,
                "couponTimeslots" => $couponTimeslots,
                "noFreeTimeslots" => $freeSlots->count() === 0,
                "noFreeCouponTimeslots" => $freeCouponSlots->count() === 0,
            ];
        }

        if ($capacityOnly) {
            return [
                "timeslots" => (object) $timeslotCapacity,
                "couponTimeslots" => (object) $couponTimeslotCapacity,
            ];
        }

        return [
            "usesCoupon" => (bool) $this->getRequest()->getVar("coupon"),
            "maxGroupSize" => (int) SiteConfig::current_site_config()->MaxGroupSize,
            "registrationFormUrl" => $this->Link("RegistrationForm"),
            "securityID" => SecurityToken::inst()->getValue(),
            "eventPageLink" => $this->Link(),
            "dates" => array_values($dates),
        ];
    }

    public function unsubscribe(HTTPRequest $request)
    {
        $hash = $request->param("ID");

        if ($hash != "") {
            $registration = Registration::get()->filter(array("Hash" => $hash))->First();
            if ($registration) {
                $registration->delete();
                return $this->redirect($this->Link("unsubscribesuccessful"));
            }
        }
        return $this->redirect($this->Link("eventnotfound"));
    }

    public function ticket(HTTPRequest $request)
    {
        $hash = $request->param("ID");

        if ($hash != "") {
            $registration = Registration::get()->filter(array("Hash" => $hash))->First();
            if ($registration) {
                return array(
                    "Registration" => $registration,
                    "TicketDataJSON" => json_encode(
                        $this->getTicketData($registration),
                        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                    ),
                );
            }
        }
        return $this->redirect($this->Link("eventnotfound"));
    }

    public function ticketdata(HTTPRequest $request)
    {
        $hash = $request->param("ID");
        $registration = $hash != "" ? Registration::get()->filter(array("Hash" => $hash))->First() : null;

        $this->response->addHeader('Content-Type', 'application/json');
        return json_encode($registration ? $this->getTicketData($registration) : array("Status" => null));
    }

    private function getTicketData(Registration $registration)
    {
        return array(
            "Status" => $registration->Status,
            "Title" => $registration->Title,
            "GroupSize" => $registration->GroupSize,
            "CouponType" => $registration->UsedCoupon()->exists() ? $registration->UsedCoupon()->Type : null,
            "EventTitle" => $registration->Event()->Title,
            "EventDateFormatted" => $registration->Event()->DateFormatted,
            "EventPlace" => $registration->Event()->Place,
            "CheckInCode" => $registration->CheckInCode,
            "SlotTimeFormatted" => $registration->TimeSlot()->SlotTimeFormatted,
            "SlotTimeEndFormatted" => $registration->TimeSlot()->SlotTimeEndFormatted,
            "QRCode" => $registration->getQRCode(),
            "FeedbackPageLink" => $this->getFeedbackPageLink(),
            "GoogleWalletLink" => $registration->getGoogleWalletLink(),
        );
    }

    public function validateticket(HTTPRequest $request)
    {
        $hash = $request->param("ID");
        $json = array();
        $json["Valid"] = false;
        $json["Message"] = "Ungültiger Code";

        if ($hash != "") {
            $registration = Registration::get()->filter(array("Hash" => $hash))->First();

            //Return JSON from the data

            if ($registration) {
                //Check if Timeslot is now plus 10 minutes and minus 10 minutes
                if ($registration->Event()->exists() && strtotime($registration->Event()->EventDate) - strtotime(date("Y-m-d H:i:s")) < 600 && $registration->Event()->EventDate()->exists() && strtotime($registration->Event()->EventDate) - strtotime(date("Y-m-d H:i:s")) > -600) {
                    $json["Valid"] = true;
                    $json["Message"] = "Ticket gültig";
                    $json["Name"] = $registration->Title;
                    $json["TimeSlot"] = $registration->TimeSlot()->Title;
                    $json["SlotTime"] = $registration->TimeSlot()->SlotTime;
                    $json["Event"] = $registration->Event()->Title;
                    $json["GroupSize"] = $registration->GroupSize;
                    $json["QRCode"] = $registration->getQRCode();
                } else {
                    $json["Valid"] = false;
                    $json["Message"] = "Ticket nicht gültig";
                    $json["TimeSlot"] = $registration->TimeSlot()->Title;
                    $json["Event"] = $registration->Event()->Title;
                }
            }
        }
        $this->response->addHeader('Content-Type', 'application/json');
        return json_encode($json);
    }

    public function getFeedbackPageLink()
    {
        $feedbackPage = FeedbackPage::get()->first();
        return $feedbackPage ? $feedbackPage->Link() : null;
    }
}
