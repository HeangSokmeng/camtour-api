<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TravelActivity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TravelActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TravelActivity::query();
        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }
        if ($request->has('image')) {
            $query->byImage($request->image);
        }
        if ($request->has('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }
        if ($request->has('min_price') && $request->has('max_price')) {
            $query->byPriceRange($request->min_price, $request->max_price);
        }
        if ($request->has('active')) {
            $query->active();
        }
        $activities = $query->paginate(10);
        foreach ($activities as $q) {
            $q->image = asset('storage/travel_activities/' . $q->image);
        }
        return res_paginate($activities, "Get all travel activities success", $activities->items());
    }

    public function show(TravelActivity $travelActivity): JsonResponse
    {
        $travelActivity->load('location');
        return res_success($travelActivity, "Travel activity retrieved successfully");
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_hours' => 'nullable|integer|min:1',
            'difficulty_level' => 'nullable|in:Easy,Moderate,Hard',
            'price_per_person' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'is_active' => 'boolean',
            'max_participants' => 'nullable|integer|min:1',
            'included_items' => 'nullable|array',
            'requirements' => 'nullable|array',
        ]);
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('travel_activities', ['disk' => 'public']);
            $validated['image'] = basename($path);
        }
        $activity = TravelActivity::create($validated);
        $activity->load('location');
        return res_success($activity, 'Travel activity created successfully');
    }

    public function update(Request $request, TravelActivity $travelActivity): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => 'nullable|exists:locations,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'duration_hours' => 'nullable|integer|min:1',
            'difficulty_level' => 'nullable|in:Easy,Moderate,Hard',
            'price_per_person' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'is_active' => 'boolean',
            'max_participants' => 'nullable|integer|min:1',
            'included_items' => 'nullable|array',
            'requirements' => 'nullable|array',
        ]);
        if ($request->hasFile('image')) {
            if ($travelActivity->image) {
                $oldImagePath = public_path('storage/travel_activities/' . $travelActivity->image);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            $path = $request->file('image')->store('travel_activities', ['disk' => 'public']);
            $validated['image'] = basename($path); // Only store the filename
        }
        $travelActivity->update($validated);
        $travelActivity->load('location');
        return res_success($travelActivity, 'Travel activity updated successfully');
    }

    public function destroy(TravelActivity $travelActivity): JsonResponse
    {
        if ($travelActivity->image) {
            $imagePath = public_path('storage/travel_activities/' . $travelActivity->image);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        $travelActivity->delete();
        return res_success(null, 'Travel activity deleted successfully');
    }

    public function getByLocation(int $locationId): JsonResponse
    {
        $activities = TravelActivity::where('location_id', $locationId)
            ->active()
            ->with('location')
            ->get();
        return res_success($activities, "Travel activities by location retrieved successfully");
    }
}
