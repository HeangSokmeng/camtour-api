<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HotelController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'star_rating' => 'required|integer|between:1,5',
            'price_per_night' => 'required|numeric|min:0',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'amenities' => 'nullable|array',
            'room_types' => 'nullable|array',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'is_active' => 'boolean'
        ]);
        $hotel = Hotel::create($request->all());
        return res_success('Hotel created successfully.', $hotel);
    }

    public function show($id): JsonResponse
    {
        $hotel = Hotel::find($id);
        if (!$hotel) return res_fail('Hotel not found.', [], 1, 404);
        return res_success('Hotel retrieved successfully.', $hotel);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $hotel = Hotel::find($id);
        if (!$hotel) return res_fail('Hotel not found.', [], 1, 404);
        $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'star_rating' => 'integer|between:1,5',
            'price_per_night' => 'numeric|min:0',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'amenities' => 'nullable|array',
            'room_types' => 'nullable|array',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'is_active' => 'boolean'
        ]);
        $hotel->update($request->all());
        return res_success('Hotel updated successfully.', $hotel);
    }

    public function destroy($id): JsonResponse
    {
        $hotel = Hotel::find($id);
        if (!$hotel) return res_fail('Hotel not found.', [], 1, 404);
        $hotel->delete();
        return res_success('Hotel deleted successfully.', null);
    }
}
