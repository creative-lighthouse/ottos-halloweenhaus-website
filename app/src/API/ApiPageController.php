<?php

namespace App\API;

use App\API\ApiPage;

use App\POS\DonationCount;

use App\POS\Sale;
use App\POS\ProductSale;

use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FileField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FormAction;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\ErrorCorrectionLevel;

use App\ImageBooth\PhotoboxGalleryPage;
use App\ImageBooth\BoothImage;
use App\Events\Event;
use App\Events\EntryLog;
use App\Events\Registration;
use App\Feedback\FeedbackEntry;
use App\ShowController\ShowControllerEntry;
use App\Statistics\PostalCodeResolver;
use App\Team\TeamMember;
use App\Wiki\Artefact;
use App\Wiki\Character;
use App\Wiki\Location;
use App\Wiki\MediaProject;
use App\Wiki\Show;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Security;
use SilverStripe\CMS\Controllers\ContentController;

/**
 * Class \ApiPageController
 *
 * @property ApiPage $dataRecord
 * @method ApiPage data()
 * @mixin ApiPage
 */
class ApiPageController extends ContentController
{
    private static $allowed_actions = [
        "checkCode",
        "checkIn",
        "enterShow",
        "acceptTicket",
        "cancelTicket",
        "addImageFromBooth",
        "BoothImageEntryForm",
        "submitBoothImage",
        "statistics",
        "addPOSSale",
        "wikiindex",
        "recentEntries",
        "addShowControllerData",
    ];

    public function index(HTTPRequest $request)
    {
        $this->response->addHeader('Content-Type', 'application/json');
        return json_encode(["message" => "API is running."]);
    }

    public function checkCode(HTTPRequest $request)
    {
        $code = $request->param("ID");

        // Accepts either the hash scanned from a QR code, or the 8-character
        // check-in code read off the ticket by hand when scanning isn't possible.
        $registration = Registration::get()->filter("Hash", $code)->first();
        if (!$registration) {
            $registration = Registration::get()->filter("CheckInCode", strtoupper($code))->first();
        }

        if ($registration) {
            $data['Valid'] = true;
            $data['Hash'] = $registration->Hash;
            $data['Name'] = $registration->Title;
            $timeslotTime = $registration->TimeSlot()->SlotTime;
            $eventdate = $registration->Event()->EventDate;
            //combine date and time into one string
            $data['TimeSlot'] = date("d.m.Y H:i", strtotime($eventdate . " " . $timeslotTime));
            $data['Event'] = $registration->Event()->Title;
            $data['EventID'] = $registration->EventID;
            $data['GroupSize'] = $registration->GroupSize;

            switch ($registration->Status) {
                case "Registered":
                    $data['Message'] = "Ticket wurde nicht bestätigt.";
                    $data['Status'] = "Registered";
                    break;
                case "CheckedIn":
                    $data['Message'] = "Ticket wurde bereits eingecheckt.";
                    $data['Status'] = "CheckedIn";
                    break;
                case "Cancelled":
                    $data['Message'] = "Ticket wurde storniert.";
                    $data['Status'] = "Cancelled";
                    break;
                default:
                    //if day is today and timeslot is not more than 20 minutes in the past or future
                    if (date("Y-m-d", strtotime($eventdate)) == date("Y-m-d") && strtotime($timeslotTime) > strtotime("-20 minutes") && strtotime($timeslotTime) < strtotime("+20 minutes")) {
                        $data['Message'] = "Ticket ist gültig.";
                        $data['Status'] = "Confirmed";
                    } else {
                        $data['Message'] = "Ticket ist aktuell nicht gültig.";
                        $data['Status'] = "Registered";
                    }
            }
        } else {
            $data['Valid'] = false;
            $data['Message'] = "Code ist ungültig.";
        }

        $this->response->addHeader('Content-Type', 'application/json');
        return json_encode($data);
    }

    public function acceptTicket(HTTPRequest $request)
    {
        $code = $request->param("ID");

        $registration = Registration::get()->filter("Hash", $code)->first();
        if (!$registration) {
            $registration = Registration::get()->filter("CheckInCode", strtoupper($code))->first();
        }

        if ($registration) {
            $registration->Status = "CheckedIn";
            $registration->write();
            $data['Valid'] = true;
            $data['Message'] = "Ticket wurde akzeptiert.";
        } else {
            $data['Valid'] = false;
            $data['Message'] = "Code ist ungültig.";
        }

        $this->response->addHeader('Content-Type', 'application/json');
        return json_encode($data);
    }

    public function cancelTicket(HTTPRequest $request)
    {
        $currentUser = Security::getCurrentUser();

        $event_id = $_GET["event"];
        $event = Event::get()->byId($event_id);
        $hash = $_GET["hash"];

        $data['Hash'] = $hash;
        $data['Event'] = $event_id;

        if (!isset($hash) || !isset($event)) {
            $data['Valid'] = true;
            $data['Message'] = "Ungültiges Event oder Hash";
            $data['GroupSize'] = 0;
        } else {
            $registration = Registration::get()->filter(array(
                "Hash" => $hash,
                "EventID" => $event_id,
            ))->First();
            if (!$registration) {
                $registration = Registration::get()->filter(array(
                    "CheckInCode" => strtoupper($hash),
                    "EventID" => $event_id,
                ))->First();
            }
        }

        if ($registration) {
            $registration->Status = "Cancelled";
            $registration->write();
            $data['Valid'] = true;
            $data['Message'] = "Ticket wurde gecancelt.";
        }

        $this->response->addHeader('Content-Type', 'application/json');
        return json_encode($data);
    }

    public function enterShow(HTTPRequest $request)
    {
        //Get data from request body
        $entereddata = json_decode($request->getBody(), true);
        $sq = $entereddata['sq'];
        $vq = $entereddata['vq'];
        $tt = $entereddata['tt'];
        $type = $entereddata['type'] ?? null;
        $vqHashes = $entereddata['vqHashes'] ?? [];

        $entryLog = EntryLog::create();
        $entryLog->SQ = $sq;
        $entryLog->VQ = $vq;
        $entryLog->EntryTime = date("Y-m-d H:i:s");
        if (in_array($type, ["Magic", "Scary", "Empty"], true)) {
            $entryLog->Type = $type;
        }
        $entryLog->write();

        if (is_array($vqHashes) && !empty($vqHashes)) {
            $registrations = Registration::get()->filter("Hash", $vqHashes);
            $entryLog->Registrations()->addMany($registrations);
        }

        $data['Valid'] = true;

        $this->response->addHeader('Content-Type', 'application/json');
        return json_encode($data);
    }

    /**
     * Returns the 10 most recent show entries (EntryLog rows). Requires a
     * valid API key, sent as the "X-Api-Key" request header.
     */
    public function recentEntries(HTTPRequest $request)
    {
        $this->response->addHeader('Content-Type', 'application/json');

        if (!ApiKey::isValidToken($request->getHeader('X-Api-Key'))) {
            $this->response->setStatusCode(401);
            return json_encode(["error" => "Ungültiger oder fehlender API-Schlüssel."]);
        }

        $entries = EntryLog::get()->sort("EntryTime", "DESC")->limit(10);

        $data = [];
        foreach ($entries as $entry) {
            $registrations = [];
            foreach ($entry->Registrations() as $registration) {
                $registrations[] = [
                    "Hash" => $registration->Hash,
                    "Title" => $registration->Title,
                    "GroupSize" => $registration->GroupSize,
                    "CouponType" => $registration->UsedCoupon()->exists() ? $registration->UsedCoupon()->Type : "Normal",
                ];
            }

            $data[] = [
                "EntryTime" => $entry->EntryTime,
                "SQ" => $entry->SQ,
                "VQ" => $entry->VQ,
                "Sum" => $entry->getTotalGuests(),
                "AdditionalInfo" => $entry->AdditionalInfo,
                "Type" => $entry->Type,
                "Registrations" => $registrations,
            ];
        }

        return json_encode($data);
    }

    /**
     * Stores the raw JSON payload posted by the external show controller
     * software (polls roughly every 5 seconds) as-is, so it can be parsed
     * and worked with later. Requires a valid API key, sent as the
     * "X-Api-Key" request header.
     */
    public function addShowControllerData(HTTPRequest $request)
    {
        $this->response->addHeader('Content-Type', 'application/json');

        if (!ApiKey::isValidToken($request->getHeader('X-Api-Key'))) {
            $this->response->setStatusCode(401);
            return json_encode(["error" => "Ungültiger oder fehlender API-Schlüssel."]);
        }

        $body = $request->getBody();
        if (!$body) {
            $this->response->setStatusCode(400);
            return json_encode(["error" => "Keine Daten empfangen."]);
        }

        $entry = ShowControllerEntry::create();
        $entry->Payload = $body;
        $entry->ReceivedAt = date("Y-m-d H:i:s");
        $entry->write();

        return json_encode(["Valid" => true, "id" => $entry->ID]);
    }

    public function checkIn(HTTPRequest $request)
    {
        $currentUser = Security::getCurrentUser();

        $event_id = $_GET["event"];
        $event = Event::get()->byId($event_id);
        $hash = $_GET["hash"];

        $data['Hash'] = $hash;
        $data['Event'] = $event_id;

        if (!isset($hash) || !isset($event)) {
            $data['Valid'] = true;
            $data['Message'] = "Ungültiges Event oder Hash";
            $data['GroupSize'] = 0;
        } else {
            $registration = Registration::get()->filter(array(
                "Hash" => $hash,
                "EventID" => $event_id,
            ))->First();
            if (!$registration) {
                $registration = Registration::get()->filter(array(
                    "CheckInCode" => strtoupper($hash),
                    "EventID" => $event_id,
                ))->First();
            }

            if ($registration) {
                if ($currentUser) {
                    $registration->Status = "CheckedIn";
                    $registration->write();

                    $data['Valid'] = true;
                    $data['Message'] = "Gast wurde eingecheckt.";
                    $data['GroupSize'] = $registration->GroupSize;
                } else {
                    $data['Valid'] = false;
                    $data['Message'] = "Nicht eingeloggt!";
                    $data['GroupSize'] = 0;
                }
            } else {
                $data['Valid'] = false;
                $data['Message'] = "Ein Fehler ist aufgetreten.";
                $data['Hash'] = $hash;
                $data['GroupSize'] = 0;
            }
        }

        $this->response->addHeader('Content-Type', 'application/json');
        return json_encode($data);
    }

    //Get Image from base64 string in API call and save to database
    public function addImageFromBooth(HTTPRequest $request)
    {
        $data = json_decode($request->getBody(), true);
        if (!isset($data['image'])) {
            $this->response->addHeader('Content-Type', 'application/json');
            return json_encode(["message" => "No image data found."]);
        }

        $photogallery = PhotoboxGalleryPage::get()->first();

        $boothImage = BoothImage::create();
        $boothImage->Base64Image = $data['image'];
        $boothImage->isVisible = true;
        $boothImage->write();
        $returndata["message"] = "Image saved.";
        $returndata["id"] = $boothImage->ID;
        $returndata["hash"] = $boothImage->HashID;

        if ($photogallery) {
            $returndata["detaillink"] = $photogallery->AbsoluteLink("foto") . "/" . $boothImage->HashID;
            $returndata["qrlink"] = $this->createQRCode($photogallery->AbsoluteLink("foto") . "/" . $boothImage->HashID);
        }

        $this->response->addHeader('Content-Type', 'application/json');
        return json_encode($returndata);
    }

    public function createQRCode(String $link)
    {
        $qrCode = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($link)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->validateResult(false)
            ->build();
        header('Content-Type: ' . $qrCode->getMimeType());

        return $qrCode->getDataUri();
    }

    public function BoothImageEntryForm()
    {
        $fields = FieldList::create([
            //            TextField::create('Name'),
            //            EmailField::create('Email'),
            TextField::create('Hash'),
            FileField::create('Image')->setFolderName('BoothImages'),
        ]);
        $actions = FieldList::create([
            FormAction::create('submit', 'Submit'),
        ]);
        return Form::create($this, 'BoothImageEntryForm', $fields, $actions)
            ->disableSecurityToken();
    }

    public function submit($data, $form)
    {
        $entry = BoothImage::create();
        $form->saveInto($entry);
        $entry->isVisible = true;
        $entry->write();

        $photogallery = PhotoboxGalleryPage::get()->first();
        if ($photogallery) {
            $returndata["detaillink"] = $photogallery->AbsoluteLink("foto") . "/" . $entry->HashID;
            $returndata["qrlink"] = $this->createQRCode($photogallery->AbsoluteLink("foto") . "/" . $entry->HashID);
        }


        $this->response->addHeader('Content-Type', 'application/json');
        return json_encode($returndata);
    }



    //STATISTICS API

    /**
     * Builds a SilverStripe ORM filter array restricting $column to the given year.
     * $year === null or "all" means no restriction (full history).
     */
    private function getYearDateFilter(string $column, ?string $year): array
    {
        if (!$year || $year === "all") {
            return [];
        }

        return [
            "$column:GreaterThanOrEqual" => "$year-01-01",
            "$column:LessThanOrEqual" => "$year-12-31",
        ];
    }

    public function statistics(HTTPRequest $request)
    {
        $this->response->addHeader('Content-Type', 'application/json');
        if (!isset($_GET["type"])) {
            return json_encode(["message" => "No valid type given."]);
        }
        $type = $_GET["type"];

        switch ($type) {
            case "Dashboard":
                $years = isset($_GET["years"]) ? array_filter(explode(",", $_GET["years"])) : [];
                if (!$years) {
                    $years = [date("Y")];
                }
                return $this->getStat_Dashboard($years);
                break;
            case "AvailableYears":
                return $this->getStat_AvailableYears();
                break;
            case "GuestsThisYear":
                return $this->getStat_GuestsThisYear(date("Y"));
                break;
            case "GuestsPerDay":
                return $this->getStat_GuestsPerDay(date("Y"));
                break;
            case "SalesPerDay":
                return $this->getStat_SalesPerDay(date("Y"));
                break;
            case "ProfitsPerDay":
                return $this->getStat_ProfitsPerDay(date("Y"));
                break;
            case "DonationsBySource":
                return $this->getStat_DonationsBySource(date("Y"));
                break;
            case "RegistrationsPerDay":
                return $this->getStat_RegistrationsPerDay(date("Y"));
                break;
            case "RegistrationAttendance":
                return $this->getStat_RegistrationAttendance(date("Y"));
                break;
            case "GuestsPerHour":
                return $this->getStat_GuestsPerHour(date("Y"));
                break;
            case "RegistrationsPerHour":
                return $this->getStat_RegistrationsPerHour(date("Y"));
                break;
            case "SalesPerHour":
                return $this->getStat_SalesPerHour(date("Y"));
                break;
            case "VQRegistrationsPerDay":
                return $this->getStat_VQRegistrationsPerDay();
                break;
            case "VQGuestsThisYear":
                return $this->getStat_VQGuestsThisYear();
                break;
            case "VQGuestsPerDay":
                return $this->getStat_VQGuestsPerDay();
                break;
            default:
                return json_encode(["message" => "No valid type given."]);
        }
    }

    /**
     * Combined payload for the Vue statistics dashboard: every section, once per
     * requested year (ByYear) and once averaged across all of them (Combined), so the
     * frontend can plot a line per selected year plus a "Durchschnitt" line in the same
     * chart. TotalGuests/ZIP origins stay summed (a total across years, not a per-day
     * rate); FeedbackRatingPerDay is already a weighted average from the merge.
     */
    public function getStat_Dashboard(array $years)
    {
        $byYear = [];
        $combined = null;

        foreach ($years as $year) {
            $section = $this->buildDashboardSection((string)$year);
            $combined = $combined === null ? $section : $this->mergeDashboardSections($combined, $section);
            $section["RegistrationOriginByZIP"] = $this->enrichZipList($section["RegistrationOriginByZIP"]);
            $section["FeedbackOriginByZIP"] = $this->enrichZipList($section["FeedbackOriginByZIP"]);
            $byYear[(int)$year] = $section;
        }

        $yearCount = count($years);
        if ($combined !== null) {
            $combined["GuestsPerDay"] = $this->averageTripletDict($combined["GuestsPerDay"], $yearCount);
            $combined["SalesPerDay"] = $this->averageNumberDict($combined["SalesPerDay"], $yearCount);
            $combined["ProfitsPerDay"] = $this->averageNumberDict($combined["ProfitsPerDay"], $yearCount);
            $combined["RegistrationsPerDay"] = $this->averageNumberDict($combined["RegistrationsPerDay"], $yearCount);
            $combined["GuestsPerHour"] = $this->averageTripletDict($combined["GuestsPerHour"], $yearCount);
            $combined["RegistrationsPerHour"] = $this->averageNumberDict($combined["RegistrationsPerHour"], $yearCount);
            $combined["SalesPerHour"] = $this->averageNumberDict($combined["SalesPerHour"], $yearCount);
            $combined["RegistrationOriginByZIP"] = $this->enrichZipList($combined["RegistrationOriginByZIP"]);
            $combined["FeedbackOriginByZIP"] = $this->enrichZipList($combined["FeedbackOriginByZIP"]);
        }
        // DonationsBySource stays summed across years (a total per source), same as
        // TotalGuests/ZIP origins - not a per-day rate that would need averaging.

        return json_encode([
            "Years" => array_map('intval', $years),
            "Combined" => $combined,
            "ByYear" => $byYear,
        ]);
    }

    /**
     * Adds resolved Ort/Kreis/Bundesland to each {ZIP, Number} entry, backed by the
     * PostalCodeLookup DB cache (see PostalCodeResolver) so this never repeatedly calls
     * the external OpenPLZ API for postal codes it has already seen.
     */
    private function enrichZipList(array $entries): array
    {
        $plzList = array_map(fn($entry) => (string)$entry['ZIP'], $entries);
        $resolved = PostalCodeResolver::create()->resolveMany($plzList);

        foreach ($entries as &$entry) {
            $plz = (string)$entry['ZIP'];
            if ($plz === '') {
                $entry['Ort'] = 'Unbekannt';
                $entry['Kreis'] = 'Unbekannt';
                $entry['Bundesland'] = 'Unbekannt';
                continue;
            }
            $location = $resolved[$plz] ?? null;
            $entry['Ort'] = $location['Ort'] ?? $plz;
            $entry['Kreis'] = $location['Kreis'] ?? $plz;
            $entry['Bundesland'] = $location['Bundesland'] ?? $plz;
        }
        unset($entry);

        return $entries;
    }

    private function averageNumberDict(array $dict, int $count): array
    {
        if ($count <= 1) {
            return $dict;
        }
        $result = [];
        foreach ($dict as $key => $value) {
            $result[$key] = round($value / $count, 2);
        }
        return $result;
    }

    private function averageTripletDict(array $dict, int $count): array
    {
        if ($count <= 1) {
            return $dict;
        }
        $result = [];
        foreach ($dict as $key => $triplet) {
            $result[$key] = [
                'VQ' => round($triplet['VQ'] / $count, 2),
                'SQ' => round($triplet['SQ'] / $count, 2),
                'TT' => round($triplet['TT'] / $count, 2),
            ];
        }
        return $result;
    }

    private function buildDashboardSection(string $year): array
    {
        return [
            "TotalGuests" => json_decode($this->getStat_GuestsThisYear($year), true)["GuestsThisYear"],
            "GuestsPerDay" => json_decode($this->getStat_GuestsPerDay($year), true),
            "SalesPerDay" => json_decode($this->getStat_SalesPerDay($year), true),
            "ProfitsPerDay" => json_decode($this->getStat_ProfitsPerDay($year), true),
            "DonationsBySource" => json_decode($this->getStat_DonationsBySource($year), true),
            "RegistrationsPerDay" => json_decode($this->getStat_RegistrationsPerDay($year), true),
            "RegistrationAttendance" => json_decode($this->getStat_RegistrationAttendance($year), true),
            "GuestsPerHour" => json_decode($this->getStat_GuestsPerHour($year), true),
            "RegistrationsPerHour" => json_decode($this->getStat_RegistrationsPerHour($year), true),
            "SalesPerHour" => json_decode($this->getStat_SalesPerHour($year), true),
            "RegistrationOriginByZIP" => json_decode($this->getStat_RegistrationOriginByZIP($year), true),
            "FeedbackOriginByZIP" => json_decode($this->getStat_FeedbackOriginByZIP($year), true),
            "FeedbackRatingPerDay" => json_decode($this->getStat_FeedbackRatingPerDay($year), true),
            "FeedbackComments" => json_decode($this->getStat_FeedbackComments($year), true),
        ];
    }

    private function mergeDashboardSections(array $a, array $b): array
    {
        return [
            "TotalGuests" => $this->mergeCountTriplet($a["TotalGuests"], $b["TotalGuests"]),
            "GuestsPerDay" => $this->mergeTripletDict($a["GuestsPerDay"], $b["GuestsPerDay"]),
            "SalesPerDay" => $this->mergeNumberDict($a["SalesPerDay"], $b["SalesPerDay"]),
            "ProfitsPerDay" => $this->mergeNumberDict($a["ProfitsPerDay"], $b["ProfitsPerDay"]),
            "DonationsBySource" => $this->mergeNumberDict($a["DonationsBySource"], $b["DonationsBySource"]),
            "RegistrationsPerDay" => $this->mergeNumberDict($a["RegistrationsPerDay"], $b["RegistrationsPerDay"]),
            "RegistrationAttendance" => $this->mergeAttendanceTriplet($a["RegistrationAttendance"], $b["RegistrationAttendance"]),
            "GuestsPerHour" => $this->mergeTripletDict($a["GuestsPerHour"], $b["GuestsPerHour"]),
            "RegistrationsPerHour" => $this->mergeNumberDict($a["RegistrationsPerHour"], $b["RegistrationsPerHour"]),
            "SalesPerHour" => $this->mergeNumberDict($a["SalesPerHour"], $b["SalesPerHour"]),
            "RegistrationOriginByZIP" => $this->mergeZipList($a["RegistrationOriginByZIP"], $b["RegistrationOriginByZIP"]),
            "FeedbackOriginByZIP" => $this->mergeZipList($a["FeedbackOriginByZIP"], $b["FeedbackOriginByZIP"]),
            "FeedbackRatingPerDay" => $this->mergeRatingDict($a["FeedbackRatingPerDay"], $b["FeedbackRatingPerDay"]),
            "FeedbackComments" => array_merge($a["FeedbackComments"], $b["FeedbackComments"]),
        ];
    }

    private function mergeCountTriplet(array $a, array $b): array
    {
        return [
            'VQ' => $a['VQ'] + $b['VQ'],
            'SQ' => $a['SQ'] + $b['SQ'],
            'TT' => $a['TT'] + $b['TT'],
        ];
    }

    private function mergeAttendanceTriplet(array $a, array $b): array
    {
        return [
            'Registered' => $a['Registered'] + $b['Registered'],
            'CheckedIn' => $a['CheckedIn'] + $b['CheckedIn'],
            'NoShow' => $a['NoShow'] + $b['NoShow'],
        ];
    }

    private function mergeTripletDict(array $a, array $b): array
    {
        $result = $a;
        foreach ($b as $key => $triplet) {
            $result[$key] = isset($result[$key]) ? $this->mergeCountTriplet($result[$key], $triplet) : $triplet;
        }
        ksort($result);
        return $result;
    }

    private function mergeNumberDict(array $a, array $b): array
    {
        $result = $a;
        foreach ($b as $key => $value) {
            $result[$key] = ($result[$key] ?? 0) + $value;
        }
        ksort($result);
        return $result;
    }

    private function mergeZipList(array $a, array $b): array
    {
        $grouped = [];
        foreach (array_merge($a, $b) as $entry) {
            $zip = $entry['ZIP'];
            $grouped[$zip] = ($grouped[$zip] ?? 0) + $entry['Number'];
        }

        arsort($grouped);

        $result = [];
        foreach ($grouped as $zip => $number) {
            $result[] = ['ZIP' => $zip, 'Number' => $number];
        }
        return $result;
    }

    private function mergeRatingDict(array $a, array $b): array
    {
        $result = $a;
        foreach ($b as $day => $entry) {
            if (!isset($result[$day])) {
                $result[$day] = $entry;
                continue;
            }
            $count = $result[$day]['Count'] + $entry['Count'];
            $stars = $result[$day]['AverageStars'] * $result[$day]['Count'] + $entry['AverageStars'] * $entry['Count'];
            $result[$day] = [
                'AverageStars' => $count ? round($stars / $count, 2) : 0,
                'Count' => $count,
            ];
        }
        ksort($result);
        return $result;
    }

    public function getStat_AvailableYears()
    {
        $years = [];
        $columnsByTable = [
            "EntryLog" => "EntryTime",
            "Registration" => "Created",
        ];

        foreach ($columnsByTable as $table => $column) {
            $result = \SilverStripe\ORM\DB::query(
                "SELECT DISTINCT YEAR(\"$column\") AS Y FROM \"$table\" WHERE \"$column\" IS NOT NULL"
            );
            foreach ($result as $row) {
                $years[(int)$row['Y']] = true;
            }
        }

        krsort($years);

        return json_encode(array_keys($years));
    }

    public function getStat_GuestsThisYear(?string $year = null)
    {
        //Get all entry logs
        $entryLogs = EntryLog::get()->filter($this->getYearDateFilter("EntryTime", $year));

        //Calculate People by groupsize of registrations
        $data['GuestsThisYear'] = [
            'VQ' => 0,
            'SQ' => 0,
            'TT' => 0,
        ];
        foreach ($entryLogs as $entryLog) {
            $entryLogVQ = $entryLog->VQ;
            $entryLogSQ = $entryLog->SQ;
            $entryLogTT = $entryLog->getTotalGuests();
            $data['GuestsThisYear']['VQ'] += $entryLogVQ;
            $data['GuestsThisYear']['SQ'] += $entryLogSQ;
            $data['GuestsThisYear']['TT'] += $entryLogTT;
        }

        return json_encode($data);
    }

    public function getStat_GuestsPerDay(?string $year = null)
    {
        //Get all entry logs
        $entryLogs = EntryLog::get()->filter($this->getYearDateFilter("EntryTime", $year));

        //Split the entry logs into days
        $days = [];

        foreach ($entryLogs as $entryLog) {
            $day = date("m-d", strtotime($entryLog->EntryTime));
            if (!isset($days[$day])) {
                $days[$day] = [
                    'VQ' => $entryLog->VQ,
                    'SQ' => $entryLog->SQ,
                    'TT' => $entryLog->getTotalGuests(),
                ];
            } else {
                $days[$day]['VQ'] += $entryLog->VQ;
                $days[$day]['SQ'] += $entryLog->SQ;
                $days[$day]['TT'] += $entryLog->getTotalGuests();
            }
        }

        //Sort the array by date
        ksort($days);

        $data = $days;

        return json_encode($data);
    }

    public function getStat_RegistrationsPerDay(?string $year = null)
    {
        //Get all entry logs
        $registrations = Registration::get()->filter($this->getYearDateFilter("Event.EventDate", $year));

        //Split the entry logs into days
        $days = [];

        foreach ($registrations as $registration) {
            $day = date("m-d", strtotime($registration->Created));
            if (!isset($days[$day])) {
                $days[$day] = $registration->GroupSize;
            } else {
                $days[$day] += $registration->GroupSize;
            }
        }

        //Sort the array by date
        ksort($days);

        $data = $days;

        return json_encode($data);
    }

    /**
     * Registered vs. checked-in people per year, in GroupSize (people, not registration
     * rows) - same unit as RegistrationsPerDay. Cancelled registrations are excluded
     * entirely: a cancellation is a known non-attendance, not a no-show.
     */
    public function getStat_RegistrationAttendance(?string $year = null)
    {
        $registrations = Registration::get()->filter($this->getYearDateFilter("Event.EventDate", $year));

        $registered = 0;
        $checkedIn = 0;
        foreach ($registrations as $registration) {
            if ($registration->Status === "Cancelled") {
                continue;
            }
            $registered += $registration->GroupSize;
            if ($registration->Status === "CheckedIn") {
                $checkedIn += $registration->GroupSize;
            }
        }

        $data = [
            'Registered' => $registered,
            'CheckedIn' => $checkedIn,
            'NoShow' => $registered - $checkedIn,
        ];

        return json_encode($data);
    }

    public function getStat_SalesPerDay(?string $year = null)
    {
        //Get all entry logs
        $sales = Sale::get()->filter($this->getYearDateFilter("SaleTime", $year));

        //Split the entry logs into days
        $days = [];

        foreach ($sales as $sale) {
            $productamount = 0;
            foreach ($sale->ProductSales() as $productSale) {
                $productamount += (int)$productSale->Amount;
            }
            $day = date("m-d", strtotime($sale->SaleTime));
            if (!isset($days[$day])) {
                $days[$day] = $productamount;
            } else {
                $days[$day] += $productamount;
            }
        }

        //Sort the array by date
        ksort($days);

        $data = $days;

        return json_encode($data);
    }

    public function getStat_ProfitsPerDay(?string $year = null)
    {
        //Get all entry logs
        $sales = Sale::get()->filter($this->getYearDateFilter("SaleTime", $year));

        $donationCounts = DonationCount::get()->filter($this->getYearDateFilter("CountDateTime", $year));

        //Split the entry logs into days
        $days = [];

        foreach ($sales as $sale) {
            $profit = 0.0;
            foreach ($sale->ProductSales() as $productSale) {
                $profit += (float)$productSale->Amount * ((float)$productSale->SellingPrice - (float)$productSale->Product()->BuyPrice);
            }
            $day = date("m-d", strtotime($sale->SaleTime));
            if (!isset($days[$day])) {
                $days[$day] = $profit;
            } else {
                $days[$day] += $profit;
            }
        }

        foreach ($donationCounts as $donationCount) {
            $day = date("m-d", strtotime($donationCount->CountDateTime));
            if (!isset($days[$day])) {
                $days[$day] = $donationCount->Amount;
            } else {
                $days[$day] += $donationCount->Amount;
            }
        }

        //Sort the array by date
        ksort($days);

        $data = $days;

        return json_encode($data);
    }

    /**
     * Donation totals grouped by DonationCount.Source (e.g. "Spendenschädel",
     * "Spendentruhe", "SumUp", "ko-fi"), sorted by amount descending. Source is a free-text
     * field, so known casing/spelling variants get folded together via
     * normalizeDonationSource() before grouping.
     */
    public function getStat_DonationsBySource(?string $year = null)
    {
        $donationCounts = DonationCount::get()->filter($this->getYearDateFilter("CountDateTime", $year));

        $grouped = [];
        foreach ($donationCounts as $donationCount) {
            $source = $this->normalizeDonationSource((string)$donationCount->Source);
            $grouped[$source] = ($grouped[$source] ?? 0) + (float)$donationCount->Amount;
        }

        arsort($grouped);

        return json_encode($grouped);
    }

    private function normalizeDonationSource(string $source): string
    {
        $source = trim($source);
        if ($source === '') {
            return 'Unbekannt';
        }

        $key = strtolower(str_replace('-', '', $source));
        $canonical = [
            'sumup' => 'SumUp',
            'kofi' => 'Ko-fi',
        ];

        return $canonical[$key] ?? $source;
    }

    public function getStat_RegistrationsPerHour(?string $year = null)
    {
        //Get all registrations for this year
        $registrations = Registration::get()->filter($this->getYearDateFilter("Event.EventDate", $year))->sort("Created");

        //Split the registrations into hours
        $hours = [];

        foreach ($registrations as $registration) {
            $hour = date("m-d H:00", strtotime($registration->Created));
            if (!isset($hours[$hour])) {
                $hours[$hour] = $registration->GroupSize;
            } else {
                $hours[$hour] += $registration->GroupSize;
            }
        }

        $data = $hours;

        return json_encode($data);
    }

    public function getStat_GuestsPerHour(?string $year = null)
    {
        //Get all entry logs
        $entryLogs = EntryLog::get()->filter($this->getYearDateFilter("EntryTime", $year))->sort("EntryTime");

        //Split the entry logs into hours
        $hours = [];

        foreach ($entryLogs as $entryLog) {
            $hour = date("m-d H:00", strtotime($entryLog->EntryTime));
            if (!isset($hours[$hour])) {
                $hours[$hour] = [
                    'VQ' => $entryLog->VQ,
                    'SQ' => $entryLog->SQ,
                    'TT' => $entryLog->getTotalGuests(),
                ];
            } else {
                $hours[$hour]['VQ'] += $entryLog->VQ;
                $hours[$hour]['SQ'] += $entryLog->SQ;
                $hours[$hour]['TT'] += $entryLog->getTotalGuests();
            }
        }

        $data = $hours;

        return json_encode($data);
    }

    public function getStat_SalesPerHour(?string $year = null)
    {
        //Get all entry logs
        $sales = Sale::get()->filter($this->getYearDateFilter("SaleTime", $year))->sort("SaleTime");

        //Split the entry logs into hours
        $hours = [];

        foreach ($sales as $sale) {
            $productamount = 0;
            foreach ($sale->ProductSales() as $productSale) {
                $productamount += (int)$productSale->Amount;
            }
            $hour = date("m-d H:00", strtotime($sale->SaleTime));
            if (!isset($hours[$hour])) {
                $hours[$hour] = $productamount;
            } else {
                $hours[$hour] += $productamount;
            }
        }

        $data = $hours;

        return json_encode($data);
    }

    public function getStat_RegistrationOriginByZIP(?string $year = null)
    {
        $registrations = Registration::get()->filter($this->getYearDateFilter("Created", $year))->sort(['ZIP' => 'ASC']);

        $grouped = [];
        foreach ($registrations as $registration) {
            $zip = $registration->ZIP ?? '';
            if (!isset($grouped[$zip])) {
                $grouped[$zip] = 0;
            }
            $grouped[$zip] += $registration->GroupSize;
        }

        arsort($grouped);

        $data = [];
        foreach ($grouped as $zip => $count) {
            $data[] = ['ZIP' => $zip, 'Number' => $count];
        }

        return json_encode($data);
    }

    public function getStat_FeedbackOriginByZIP(?string $year = null)
    {
        $feedbacks = FeedbackEntry::get()->filter($this->getYearDateFilter("Created", $year))->sort(['PLZ' => 'ASC']);

        $grouped = [];
        foreach ($feedbacks as $feedback) {
            $plz = $feedback->PLZ ?? '';
            if (!isset($grouped[$plz])) {
                $grouped[$plz] = 0;
            }
            $grouped[$plz]++;
        }

        arsort($grouped);

        $data = [];
        foreach ($grouped as $zip => $count) {
            $data[] = ['ZIP' => $zip, 'Number' => $count];
        }

        return json_encode($data);
    }

    public function getStat_FeedbackRatingPerDay(?string $year = null)
    {
        $feedbacks = FeedbackEntry::get()->filter($this->getYearDateFilter("Created", $year));

        $days = [];
        foreach ($feedbacks as $f) {
            $d = $f->Day ? date("m-d", strtotime($f->Day)) : '';
            if (!isset($days[$d])) {
                $days[$d] = ['stars' => 0, 'count' => 0];
            }
            $days[$d]['stars'] += $f->Stars;
            $days[$d]['count']++;
        }

        ksort($days);

        $data = [];
        foreach ($days as $d => $v) {
            $data[$d] = [
                'AverageStars' => $v['count'] ? round($v['stars'] / $v['count'], 2) : 0,
                'Count' => $v['count'],
            ];
        }

        return json_encode($data);
    }

    public function getStat_FeedbackComments(?string $year = null)
    {
        $filter = array_merge($this->getYearDateFilter("Created", $year), [
            'Comment:Not' => ['', null],
        ]);
        $feedbacks = FeedbackEntry::get()->filter($filter)->sort('Created', 'DESC');

        $data = [];
        foreach ($feedbacks as $feedback) {
            $data[] = [
                'Comment' => $feedback->Comment,
                'Day' => $feedback->Day ? date('d.m.', strtotime($feedback->Day)) : '',
                'Year' => $feedback->Day ? (int)date('Y', strtotime($feedback->Day)) : null,
                'Stars' => $feedback->Stars,
            ];
        }

        return json_encode($data);
    }

    public function getStat_VQGuestsThisYear()
    {
        //Get all registrations for this year
        $registrations = Registration::get()->filter(array(
            "Event.EventDate:GreaterThanOrEqual" => date("Y-01-01"),
            "Event.EventDate:LessThanOrEqual" => date("Y-12-31"),
            "Status" => "CheckedIn",
        ));

        //Calculate People by groupsize of registrations
        $data['GuestsThisYear'] = 0;
        foreach ($registrations as $registration) {
            $data['GuestsThisYear'] += $registration->GroupSize;
        }

        return json_encode($data);
    }

    public function getStat_VQGuestsPerDay()
    {
        //Get all registrations for this year
        $registrations = Registration::get()->filter(array(
            "Event.EventDate:GreaterThanOrEqual" => date("Y-01-01"),
            "Event.EventDate:LessThanOrEqual" => date("Y-12-31"),
            "Status" => "CheckedIn",
        ));

        //Split the registrations into days
        $days = [];

        foreach ($registrations as $registration) {
            $day = date("Y-m-d", strtotime($registration->Event()->EventDate));
            if (!isset($days[$day])) {
                $days[$day] = $registration->GroupSize;
            } else {
                $days[$day] += $registration->GroupSize;
            }
        }

        $data['GuestsPerDay'] = $days;

        return json_encode($data);
    }

    public function getStat_VQRegistrationsPerDay()
    {
        //Get all registrations for this year
        $registrations = Registration::get()->filter(array(
            "Event.EventDate:GreaterThanOrEqual" => date("Y-01-01"),
            "Event.EventDate:LessThanOrEqual" => date("Y-12-31"),
        ));

        //Split the registrations into days
        $days = [];

        foreach ($registrations as $registration) {
            $day = date("Y-m-d", strtotime($registration->Created));
            if (!isset($days[$day])) {
                $days[$day] = 1;
            } else {
                $days[$day] += 1;
            }
        }

        $data['RegistrationsPerDay'] = $days;

        return json_encode($data);
    }

    public function addPOSSale(HTTPRequest $request)
    {
        $currentUser = Security::getCurrentUser();

        if ($currentUser) {
            if ($request->getBody() == null) {
                $this->response->addHeader('Content-Type', 'application/json');
                return json_encode(["message" => "No data found."]);
            }

            //Get data from request body
            $entereddata = json_decode($request->getBody(), true);
            $products = $entereddata['products'];
            $total = $entereddata['total'];

            $sale = Sale::create();
            $sale->SaleTime = date("Y-m-d H:i:s");
            $sale->write();

            foreach ($products as $product) {
                $productSale = ProductSale::create();
                $productSale->ProductID = $product['id'];
                $productSale->Amount = $product['amount'];
                $productSale->SellingPrice = $product['price'];
                $productSale->ParentID = $sale->ID;
                $productSale->write();
            }

            $sale->TotalPrice = $total;
            $sale->write();

            $data['Valid'] = true;

            $this->response->addHeader('Content-Type', 'application/json');
            return json_encode($data);
        } else {
            $this->response->addHeader('Content-Type', 'application/json');
            return json_encode(["message" => "Not logged in."]);
        }
    }

    public function wikiindex(HTTPRequest $request)
    {
        $this->response->addHeader('Content-Type', 'application/json');

        $shows = Show::get()->sort('Title', 'ASC');
        $characters = Character::get()->sort('Title', 'ASC');
        $teammembers = TeamMember::get()->sort('Title', 'ASC');
        $artefacts = Artefact::get()->sort('Title', 'ASC');
        $places = Location::get()->sort('Title', 'ASC');
        $mediaprojects = MediaProject::get()->sort('Title', 'ASC');

        $data = [];

        //Create an index for all wiki relevant items with a link to them
        foreach ($shows as $show) {
            $data[] = ['type' => 'show', 'title' => $show->Title, 'year' => $show->Year, 'link' => $show->getLink()];
            foreach ($this->parseGlossaryTerms($show->GlossaryTerms) as $term) {
                $data[] = ['type' => 'show', 'title' => $term, 'link' => $show->getLink()];
            }
        }
        foreach ($characters as $character) {
            $data[] = ['type' => 'character', 'title' => $character->Title, 'link' => $character->getLink()];
            foreach ($this->parseGlossaryTerms($character->GlossaryTerms) as $term) {
                $data[] = ['type' => 'character', 'title' => $term, 'link' => $character->getLink()];
            }
        }
        foreach ($teammembers as $teammember) {
            $data[] = ['type' => 'teammember', 'title' => $teammember->Title, 'link' => $teammember->getLink()];
        }
        foreach ($artefacts as $artefact) {
            $data[] = ['type' => 'artefact', 'title' => $artefact->Title, 'link' => $artefact->getLink()];
            foreach ($this->parseGlossaryTerms($artefact->GlossaryTerms) as $term) {
                $data[] = ['type' => 'artefact', 'title' => $term, 'link' => $artefact->getLink()];
            }
        }
        foreach ($places as $place) {
            $data[] = ['type' => 'location', 'title' => $place->Title, 'link' => $place->getLink()];
            foreach ($this->parseGlossaryTerms($place->GlossaryTerms) as $term) {
                $data[] = ['type' => 'location', 'title' => $term, 'link' => $place->getLink()];
            }
        }
        foreach ($mediaprojects as $mediaproject) {
            $data[] = ['type' => 'mediaproject', 'title' => $mediaproject->Title, 'link' => $mediaproject->getLink()];
            foreach ($this->parseGlossaryTerms($mediaproject->GlossaryTerms) as $term) {
                $data[] = ['type' => 'mediaproject', 'title' => $term, 'link' => $mediaproject->getLink()];
            }
        }

        return json_encode($data);
    }

    private function parseGlossaryTerms(?string $terms): array
    {
        if (!$terms) {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(';', $terms))));
    }
}
