<?php

namespace App\Statistics;

use SilverStripe\Core\Injector\Injectable;

/**
 * Resolves German postal codes to {Ort, Kreis, Bundesland} via the free OpenPLZ API
 * (openplzapi.org), backed by the PostalCodeLookup DB cache so each PLZ only ever
 * hits the external API once.
 */
class PostalCodeResolver
{
    use Injectable;

    private const API_URL = 'https://openplzapi.org/de/Localities?postalCode=';

    /**
     * @param string[] $plzList
     * @return array<string, array{Ort: string, Kreis: string, Bundesland: string}>
     */
    public function resolveMany(array $plzList): array
    {
        $plzList = array_values(array_unique(array_filter($plzList, fn($plz) => $plz !== '' && $plz !== null)));
        $result = [];

        if (!$plzList) {
            return $result;
        }

        $cached = PostalCodeLookup::get()->filter('PLZ', $plzList);
        foreach ($cached as $entry) {
            $result[$entry->PLZ] = $this->toResult($entry->PLZ, $entry->Ort, $entry->Kreis, $entry->Bundesland);
        }

        foreach ($plzList as $plz) {
            if (isset($result[$plz])) {
                continue;
            }
            $result[$plz] = $this->fetchAndCache($plz);
        }

        return $result;
    }

    private function fetchAndCache(string $plz): array
    {
        $location = $this->fetchFromApi($plz);

        $entry = PostalCodeLookup::create();
        $entry->PLZ = $plz;
        $entry->Ort = $location['Ort'] ?? '';
        $entry->Kreis = $location['Kreis'] ?? '';
        $entry->Bundesland = $location['Bundesland'] ?? '';
        $entry->Resolved = $location !== null;
        $entry->write();

        return $this->toResult($plz, $entry->Ort, $entry->Kreis, $entry->Bundesland);
    }

    private function toResult(string $plz, ?string $ort, ?string $kreis, ?string $bundesland): array
    {
        return [
            'Ort' => $ort ?: $plz,
            'Kreis' => $kreis ?: $plz,
            'Bundesland' => $bundesland ?: $plz,
        ];
    }

    private const NEIGHBOR_SEARCH_RADIUS = 20;

    /**
     * Some postal codes (PO boxes, large-customer-only codes) have no locality entry
     * of their own, so an exact lookup 404s even though the code clearly sits inside a
     * real Kreis/Bundesland (and, for a tight gap, in the very same town). Falls back to
     * the nearest neighbouring postal code that does resolve - German PLZ ranges are
     * geographically contiguous and gaps are almost always a handful of codes wide, so
     * the nearest neighbour's Ort/Kreis/Bundesland is a reliable stand-in.
     */
    private function fetchFromApi(string $plz): ?array
    {
        $exact = $this->fetchLocalityFromApi($plz);
        if ($exact !== null) {
            return $exact;
        }

        $numericPlz = (int)$plz;
        if ($numericPlz <= 0) {
            return null;
        }

        for ($distance = 1; $distance <= self::NEIGHBOR_SEARCH_RADIUS; $distance++) {
            foreach ([$numericPlz - $distance, $numericPlz + $distance] as $candidate) {
                if ($candidate < 1 || $candidate > 99999) {
                    continue;
                }
                $neighbor = $this->fetchLocalityFromApi(str_pad((string)$candidate, 5, '0', STR_PAD_LEFT));
                if ($neighbor !== null) {
                    return $neighbor;
                }
            }
        }

        return null;
    }

    private function fetchLocalityFromApi(string $plz): ?array
    {
        $ch = curl_init(self::API_URL . rawurlencode($plz));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false || $httpCode !== 200) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data[0])) {
            return null;
        }

        $locality = $data[0];
        return [
            'Ort' => $locality['name'] ?? '',
            'Kreis' => $locality['district']['name'] ?? '',
            'Bundesland' => $locality['federalState']['name'] ?? '',
        ];
    }
}
