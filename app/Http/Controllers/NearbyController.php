<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NearbyController extends Controller
{
    public function getNearbyDestinations($destinationId): JsonResponse
    {
        $destination = Destination::active()->find($destinationId);
        if (!$destination) {
            return res_fail('Destination not found.', [], 1, 404);
        }
        $nearby = $destination->nearby_attractions ?? [];
        $nearbyWithDetails = [];
        foreach ($nearby as $attraction) {
            $nearbyDestination = Destination::where('name', 'like', '%' . $attraction['name'] . '%')->first();

            $attractionData = [
                'name' => $attraction['name'],
                'distance_km' => $attraction['distance_km'] ?? 0,
                'cost' => $attraction['cost'] ?? 0,
                'estimated_time' => $this->calculateTravelTime($attraction['distance_km'] ?? 0)
            ];
            if ($nearbyDestination) {
                $attractionData = array_merge($attractionData, [
                    'id' => $nearbyDestination->id,
                    'description' => $nearbyDestination->description,
                    'entrance_fee' => $nearbyDestination->entrance_fee,
                    'transport_fee' => $nearbyDestination->transport_fee,
                    'recommended_duration_hours' => $nearbyDestination->recommended_duration_hours,
                    'requires_guide' => $nearbyDestination->requires_guide,
                    'guide_fee' => $nearbyDestination->guide_fee,
                    'total_cost' => $nearbyDestination->total_cost
                ]);
            }
            $nearbyWithDetails[] = $attractionData;
        }
        $data = [
            'primary_destination' => [
                'id' => $destination->id,
                'name' => $destination->name,
                'description' => $destination->description
            ],
            'nearby_attractions' => $nearbyWithDetails,
            'total_nearby' => count($nearbyWithDetails)
        ];
        return res_success('Nearby destinations retrieved successfully.', $data);
    }

    public function getNearbyAttractions($destinationName): JsonResponse
    {
        $destination = Destination::active()
            ->where('name', 'like', '%' . $destinationName . '%')
            ->first();
        if (!$destination) {
            return res_fail('Destination not found.', [], 1, 404);
        }
        return $this->getNearbyDestinations($destination->id);
    }

    public function getCustomNearbyRecommendations(Request $request): JsonResponse
    {
        $request->validate([
            'destination_name' => 'required|string',
            'budget' => 'required|numeric|min:0',
            'party_size' => 'required|integer|min:1',
            'max_distance_km' => 'nullable|numeric|min:0|max:50',
            'include_guides' => 'boolean',
            'max_duration_hours' => 'nullable|integer|min:1|max:12'
        ]);
        $destination = Destination::active()
            ->where('name', 'like', '%' . $request->destination_name . '%')
            ->first();
        if (!$destination) {
            return res_fail('Destination not found.', [], 1, 404);
        }
        $maxDistance = $request->max_distance_km ?? 10; // Default 10km radius
        $includeGuides = $request->include_guides ?? false;
        $maxDuration = $request->max_duration_hours ?? 8;
        $nearby = $destination->nearby_attractions ?? [];
        $recommendations = [];
        $totalCost = 0;
        $totalDuration = 0;
        $mainCost = $destination->entrance_fee + $destination->transport_fee;
        if ($includeGuides && $destination->requires_guide) {
            $mainCost += $destination->guide_fee ?? 0;
        }
        $mainCost *= $request->party_size;
        $recommendations[] = [
            'type' => 'primary',
            'name' => $destination->name,
            'description' => $destination->description,
            'cost_per_person' => $mainCost / $request->party_size,
            'total_cost' => $mainCost,
            'duration_hours' => $destination->recommended_duration_hours,
            'distance_km' => 0,
            'requires_guide' => $destination->requires_guide
        ];
        $totalCost += $mainCost;
        $totalDuration += $destination->recommended_duration_hours;
        foreach ($nearby as $attraction) {
            $attractionDistance = $attraction['distance_km'] ?? 0;
            if ($attractionDistance > $maxDistance) {
                continue;
            }
            $nearbyDestination = Destination::where('name', 'like', '%' . $attraction['name'] . '%')->first();
            if ($nearbyDestination) {
                $attractionCost = $nearbyDestination->entrance_fee + $nearbyDestination->transport_fee;
                if ($includeGuides && $nearbyDestination->requires_guide) {
                    $attractionCost += $nearbyDestination->guide_fee ?? 0;
                }
                $attractionCost *= $request->party_size;
                $attractionDuration = $nearbyDestination->recommended_duration_hours;
                if (($totalCost + $attractionCost) <= $request->budget &&
                    ($totalDuration + $attractionDuration) <= $maxDuration
                ) {
                    $recommendations[] = [
                        'type' => 'nearby',
                        'name' => $nearbyDestination->name,
                        'description' => $nearbyDestination->description,
                        'cost_per_person' => $attractionCost / $request->party_size,
                        'total_cost' => $attractionCost,
                        'duration_hours' => $attractionDuration,
                        'distance_km' => $attractionDistance,
                        'travel_time_minutes' => $this->calculateTravelTime($attractionDistance),
                        'requires_guide' => $nearbyDestination->requires_guide,
                        'budget_remaining_after' => $request->budget - ($totalCost + $attractionCost)
                    ];
                    $totalCost += $attractionCost;
                    $totalDuration += $attractionDuration;
                }
            }
        }
        $data = [
            'destination_name' => $destination->name,
            'budget' => $request->budget,
            'party_size' => $request->party_size,
            'total_estimated_cost' => $totalCost,
            'total_duration_hours' => $totalDuration,
            'budget_remaining' => $request->budget - $totalCost,
            'budget_status' => $totalCost <= $request->budget ? 'within_budget' : 'over_budget',
            'recommendations' => $recommendations,
            'summary' => [
                'total_attractions' => count($recommendations),
                'primary_attractions' => 1,
                'nearby_attractions' => count($recommendations) - 1,
                'cost_per_person' => $totalCost / $request->party_size,
                'average_distance_km' => $this->calculateAverageDistance($recommendations)
            ]
        ];
        return res_success('Custom nearby recommendations generated successfully.', $data);
    }

    public function getDestinationsWithinRadius(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_km' => 'required|numeric|min:0.1|max:100'
        ]);
        $destinations = Destination::active()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();
        $nearbyDestinations = [];
        foreach ($destinations as $destination) {
            $distance = $this->calculateDistance(
                $request->latitude,
                $request->longitude,
                $destination->latitude,
                $destination->longitude
            );
            if ($distance <= $request->radius_km) {
                $nearbyDestinations[] = [
                    'id' => $destination->id,
                    'name' => $destination->name,
                    'description' => $destination->description,
                    'distance_km' => round($distance, 2),
                    'latitude' => $destination->latitude,
                    'longitude' => $destination->longitude,
                    'entrance_fee' => $destination->entrance_fee,
                    'transport_fee' => $destination->transport_fee,
                    'total_cost' => $destination->total_cost,
                    'recommended_duration_hours' => $destination->recommended_duration_hours,
                    'requires_guide' => $destination->requires_guide
                ];
            }
        }
        usort($nearbyDestinations, function ($a, $b) {
            return $a['distance_km'] <=> $b['distance_km'];
        });
        $data = [
            'search_center' => [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude
            ],
            'radius_km' => $request->radius_km,
            'total_found' => count($nearbyDestinations),
            'destinations' => $nearbyDestinations
        ];
        return res_success('Destinations within radius retrieved successfully.', $data);
    }

    private function calculateTravelTime($distanceKm): int
    {
        $averageSpeedKmh = 30;
        return (int) round(($distanceKm / $averageSpeedKmh) * 60); // Return minutes
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
    private function calculateAverageDistance($recommendations): float
    {
        $totalDistance = 0;
        $count = 0;
        foreach ($recommendations as $rec) {
            if (isset($rec['distance_km'])) {
                $totalDistance += $rec['distance_km'];
                $count++;
            }
        }
        return $count > 0 ? round($totalDistance / $count, 2) : 0;
    }
}
