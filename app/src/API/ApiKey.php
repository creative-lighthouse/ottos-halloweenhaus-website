<?php

namespace App\API;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;

/**
 * Class \App\API\ApiKey
 *
 * @property ?string $Title
 * @property ?string $Token
 * @property bool $Active
 * @property ?string $LastUsed
 */
class ApiKey extends DataObject
{
    private static $db = [
        "Title" => "Varchar(255)",
        "Token" => "Varchar(64)",
        "Active" => "Boolean",
        "LastUsed" => "Datetime",
    ];

    private static $defaults = [
        "Active" => true,
    ];

    private static $field_labels = [
        "Title" => "Bezeichnung",
        "Token" => "Schlüssel",
        "Active" => "Aktiv",
        "LastUsed" => "Zuletzt verwendet",
    ];

    private static $summary_fields = [
        "Title" => "Bezeichnung",
        "Token" => "Schlüssel",
        "Active" => "Aktiv",
        "LastUsed" => "Zuletzt verwendet",
    ];

    private static $searchable_fields = [
        "Title",
        "Token",
    ];

    private static $default_sort = "Created DESC";

    private static $table_name = "ApiKey";

    private static $singular_name = "API-Schlüssel";
    private static $plural_name = "API-Schlüssel";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName("Token");
        $fields->removeByName("LastUsed");
        $fields->removeByName("Active");

        // The token is generated once in onBeforeWrite() and must never be
        // hand-edited, otherwise anything already issued with it would break.
        if ($this->Token) {
            $fields->addFieldToTab("Root.Main", ReadonlyField::create("Token", "Schlüssel", $this->Token));
        } else {
            $fields->addFieldToTab("Root.Main", ReadonlyField::create(
                "TokenPlaceholder",
                "Schlüssel",
                "Wird beim Speichern automatisch generiert"
            ));
        }

        $fields->addFieldToTab("Root.Main", CheckboxField::create("Active", "Aktiv"));

        if ($this->LastUsed) {
            $fields->addFieldToTab("Root.Main", ReadonlyField::create("LastUsed", "Zuletzt verwendet", $this->LastUsed));
        }

        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!$this->Token) {
            $this->Token = bin2hex(random_bytes(32));
        }
    }

    /**
     * Checks whether $token belongs to a currently active API key, and
     * records the current time as its last-used timestamp if so.
     */
    public static function isValidToken(?string $token): bool
    {
        if (!$token) {
            return false;
        }

        $apiKey = static::get()->filter(["Token" => $token, "Active" => true])->first();
        if (!$apiKey) {
            return false;
        }

        $apiKey->LastUsed = date("Y-m-d H:i:s");
        $apiKey->write();

        return true;
    }
}
