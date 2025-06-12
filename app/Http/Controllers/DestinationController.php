<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DestinationController extends Controller
{
    /**
     * Store a newly created destination
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'entrance_fee' => 'numeric|min:0',
            'transport_fee' => 'numeric|min:0',
            'nearby_attractions' => 'nullable|array',
            'age_recommendations' => 'nullable|array',
            'recommended_duration_hours' => 'integer|min:1',
            'best_time_to_visit' => 'nullable|string',
            'requires_guide' => 'boolean',
            'guide_fee' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);
        $destination = Destination::create($request->all());
        return res_success('Destination created successfully.', $destination);
    }

    /**
     * Display the specified destination
     */
    public function show($id): JsonResponse
    {
        $destination = Destination::find($id);
        if (!$destination) {
            return res_fail('Destination not found.', [], 1, 404);
        }
        return res_success('Destination retrieved successfully.', $destination);
    }

    /**
     * Update the specified destination
     */
    public function update(Request $request, $id): JsonResponse
    {
        $destination = Destination::find($id);
        if (!$destination) {
            return res_fail('Destination not found.', [], 1, 404);
        }
        $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'entrance_fee' => 'numeric|min:0',
            'transport_fee' => 'numeric|min:0',
            'nearby_attractions' => 'nullable|array',
            'age_recommendations' => 'nullable|array',
            'recommended_duration_hours' => 'integer|min:1',
            'best_time_to_visit' => 'nullable|string',
            'requires_guide' => 'boolean',
            'guide_fee' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);
        $destination->update($request->all());
        return res_success('Destination updated successfully.', $destination);
    }

    /**
     * Remove the specified destination
     */
    public function destroy($id): JsonResponse
    {
        $destination = Destination::find($id);
        if (!$destination) {
            return res_fail('Destination not found.', [], 1, 404);
        }
        $destination->delete();
        return res_success('Destination deleted successfully.', null);
    }
}
