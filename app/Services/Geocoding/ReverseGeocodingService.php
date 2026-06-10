<?php

namespace App\Services\Geocoding;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Resolves GPS coordinates to a human-readable postal address via OpenStreetMap Nominatim.
 */
class ReverseGeocodingService
{
    /**
     * Reverse-geocode WGS84 coordinates to a full display address.
     */
    public function resolveAddress(float $latitude, float $longitude): ?string
    {
        if (! config('attendance.geocoding_enabled', true)) {
            return null;
        }

        $baseUrl = rtrim((string) config('attendance.nominatim_url', 'https://nominatim.openstreetmap.org'), '/');
        $userAgent = (string) config('attendance.geocoding_user_agent', 'ManufactureERP/1.0');

        try {
            $response = Http::withHeaders([
                'User-Agent' => $userAgent,
                'Accept' => 'application/json',
            ])
                ->timeout(12)
                ->get($baseUrl.'/reverse', [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'format' => 'jsonv2',
                    'addressdetails' => 1,
                    'zoom' => 18,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();
            if (! is_array($json)) {
                return null;
            }

            $displayName = $json['display_name'] ?? null;
            if (is_string($displayName) && $displayName !== '') {
                return $this->truncateAddress($displayName);
            }

            $address = $json['address'] ?? null;
            if (! is_array($address)) {
                return null;
            }

            $built = $this->buildFromComponents($address);

            return $built !== '' ? $this->truncateAddress($built) : null;
        } catch (\Throwable $e) {
            Log::warning('Reverse geocoding failed', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function buildFromComponents(array $address): string
    {
        $parts = [];

        foreach ([
            'house_number',
            'road',
            'neighbourhood',
            'suburb',
            'city',
            'town',
            'village',
            'county',
            'state',
            'postcode',
            'country',
        ] as $key) {
            $value = $address[$key] ?? null;
            if (is_string($value) && $value !== '' && ! in_array($value, $parts, true)) {
                $parts[] = $value;
            }
        }

        return implode(', ', $parts);
    }

    private function truncateAddress(string $address): string
    {
        if (strlen($address) <= 500) {
            return $address;
        }

        return substr($address, 0, 497).'...';
    }
}
