<?php

namespace App\Statistics;

use SilverStripe\ORM\DataObject;

/**
 * Class \App\Statistics\PostalCodeLookup
 *
 * Permanent cache of PLZ -> {Ort, Kreis, Bundesland} lookups from the OpenPLZ API
 * (openplzapi.org). Postal-code-to-location mappings don't change, so once a PLZ is
 * resolved here the external API is never queried for it again.
 *
 * @property ?string $PLZ
 * @property ?string $Ort
 * @property ?string $Kreis
 * @property ?string $Bundesland
 * @property bool $Resolved
 */
class PostalCodeLookup extends DataObject
{
    private static $table_name = 'PostalCodeLookup';

    private static $db = [
        'PLZ' => 'Varchar(10)',
        'Ort' => 'Varchar(255)',
        'Kreis' => 'Varchar(255)',
        'Bundesland' => 'Varchar(255)',
        'Resolved' => 'Boolean',
    ];

    private static $indexes = [
        'PLZ' => true,
    ];
}
