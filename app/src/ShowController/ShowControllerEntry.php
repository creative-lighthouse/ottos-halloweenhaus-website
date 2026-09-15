<?php

namespace App\ShowController;

use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\ORM\DataObject;

/**
 * Class \App\ShowController\ShowControllerEntry
 *
 * Raw JSON snapshot received from the external show controller software,
 * which posts an update roughly every 5 seconds. Stored as-is for now so it
 * can be parsed/aggregated later once it's clear which fields matter.
 *
 * @property ?string $Payload
 * @property ?string $ReceivedAt
 */
class ShowControllerEntry extends DataObject
{
    private static $db = [
        "Payload" => "Text",
        "ReceivedAt" => "Datetime",
    ];

    private static $default_sort = "ReceivedAt DESC";

    private static $field_labels = [
        "Payload" => "Daten (JSON)",
        "ReceivedAt" => "Empfangen am",
    ];

    private static $summary_fields = [
        "ReceivedAt" => "Empfangen am",
        "PayloadPreview" => "Vorschau",
    ];

    private static $searchable_fields = [
        "Payload",
    ];

    private static $table_name = "ShowControllerEntry";

    private static $singular_name = "Show-Controller-Eintrag";
    private static $plural_name = "Show-Controller-Einträge";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName("ReceivedAt");
        $fields->removeByName("Payload");

        $fields->addFieldToTab("Root.Main", ReadonlyField::create("ReceivedAt", "Empfangen am", $this->ReceivedAt));
        $fields->addFieldToTab("Root.Main", TextareaField::create("Payload", "Daten (JSON)")
            ->setRows(20)
            ->setReadonly(true));

        return $fields;
    }

    public function getPayloadPreview()
    {
        $payload = (string) $this->Payload;
        return strlen($payload) > 80 ? substr($payload, 0, 80) . "…" : $payload;
    }
}
