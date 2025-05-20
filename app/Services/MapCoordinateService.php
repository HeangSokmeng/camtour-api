<?php

namespace App\Services;

/**
 * Service to extract latitude and longitude from various map URLs
 */
class MapCoordinateService
{
    /**
     * Extract latitude and longitude from a Google Maps URL
     *
     * @param string $url The Google Maps URL to extract coordinates from
     * @return array|null Array with 'lat' and 'lot' keys or null if extraction failed
     */
    public function extractCoordinatesFromUrl($url)
    {
        \Log::info('Extracting coordinates from URL: ' . $url);

        // Pattern for the standard Google Maps URL with q parameter
        if (preg_match('/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
            $coordinates = [
                'lat' => (float)$matches[1],
                'lot' => (float)$matches[2]
            ];

            \Log::info('Extracted coordinates (q parameter): ', $coordinates);
            return $coordinates;
        }

        // Pattern for complex Google Maps URL with 3d and 4d parameters
        if (preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $url, $matches)) {
            $coordinates = [
                'lat' => (float)$matches[1],
                'lot' => (float)$matches[2]
            ];

            \Log::info('Extracted coordinates (3d/4d parameters): ', $coordinates);
            return $coordinates;
        }

        \Log::info('No coordinates found in URL');
        return null;
    }
    /**
     * Extract coordinates directly from URL patterns
     */
    public function extractFromUrlPattern($url)
    {
        // Pattern for @lat,lng format common in Google Maps
        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
            return [
                'lat' => (float) $matches[1],
                'lot' => (float) $matches[2]  // Using 'lot' to match your model field
            ];
        }

        // Pattern for ll=lat,lng or sll=lat,lng
        if (preg_match('/[?&](ll|sll)=(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
            return [
                'lat' => (float) $matches[2],
                'lot' => (float) $matches[3]
            ];
        }

        return null;
    }

    /**
     * Follow URL redirects to get the final URL
     */
    private function followRedirect($url)
    {
        $options = [
            'http' => [
                'method' => 'HEAD',
                'follow_location' => 0,
                'header' => "User-Agent: Mozilla/5.0 (compatible; LocationApp/1.0)\r\n"
            ]
        ];

        $context = stream_context_create($options);

        // Make HEAD request
        $headers = @get_headers($url, 1, $context);

        if ($headers && isset($headers['Location'])) {
            // Return the location from the redirect
            return is_array($headers['Location'])
                ? end($headers['Location'])
                : $headers['Location'];
        }

        return null;
    }

    /**
     * Extract a specific query parameter from URL
     */
    private function extractQueryParam($url, $param)
    {
        $parts = parse_url($url);
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            return isset($query[$param]) ? $query[$param] : null;
        }
        return null;
    }

    /**
     * Geocode an address to get coordinates
     * Note: This requires the Google Maps Geocoding API key
     */
    private function geocodeAddress($address)
    {
        // Replace with your actual API key
        $apiKey = config('services.google_maps.key', '');

        if (empty($apiKey)) {
            return null;
        }

        $address = urlencode($address);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$apiKey}";

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if ($data['status'] === 'OK' && !empty($data['results'][0]['geometry']['location'])) {
            $location = $data['results'][0]['geometry']['location'];
            return [
                'lat' => $location['lat'],
                'lot' => $location['lng']
            ];
        }

        return null;
    }
}
