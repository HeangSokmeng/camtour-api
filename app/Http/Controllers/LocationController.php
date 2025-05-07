<?php

namespace App\Http\Controllers;

use ApiResponse;
use App\Http\Resources\LocationDetailResource;
use App\Http\Resources\LocationIndexResource;
use App\Models\Location;
use App\Models\User;
use App\Models\WishlistItem;
use App\Services\MapCoordinateService;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Support\Facades\Storage;

class LocationController extends Controller
{
    public function extractCoordinates(Request $req)
    {
        $req->validate([
            'url' => 'required|url'
        ]);
        $url = $req->input('url');
        $mapService = new MapCoordinateService();
        $coordinates = $mapService->extractCoordinatesFromUrl($url);
        if ($coordinates) return res_success('Coordinates extracted successfully.', $coordinates);
        return ApiResponse::NotFound('Could not extract coordinates from the provided URL.');
    }

    /**
     * Store a new location
     *
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $req)
    {
        if ($req->filled('url_location') && (!$req->filled('lat') || !$req->filled('lot'))) {
            $mapService = new MapCoordinateService();
            $coordinates = $mapService->extractCoordinatesFromUrl($req->input('url_location'));
            if ($coordinates) {
                $req->merge([
                    'lat' => $coordinates['lat'],
                    'lot' => $coordinates['lot']
                ]);
            }
        }
        // validation
        $req->merge(['tag_ids' => json_decode($req->input('tag_ids')) ?? []]);
        $req->validate([
            'name' => 'required|string|max:250',
            'name_local' => 'required|string|max:250',
            'thumbnail' => 'nullable|image|mimetypes:image/png,image/jpeg|max:2048',
            'url_location' => 'nullable|url',
            'short_description' => 'nullable|string|max:250',
            'description' => 'nullable|string|max:65530',
            'lat' => 'nullable|numeric|min:0',
            'lot' => 'nullable|numeric|min:0',
            'min_money' => 'nullable|numeric|min:0',
            'max_money' => 'nullable|numeric|min:0',
            'category_id' => 'required|integer|min:1|exists:categories,id,is_deleted,0',
            'province_id' => 'required|integer|min:1|exists:provinces,id',
            'district_id' => 'required|integer|min:1|exists:districts,id,is_deleted,0',
            'commune_id' => 'required|integer|min:1|exists:communes,id,is_deleted,0',
            'village_id' => 'required|integer|min:1|exists:villages,id',
            'tag_ids' => 'required|array|min:0|max:5',
            'tag_ids.*' => 'integer|min:1|exists:tags,id',
            'published_at' => 'nullable|date|date_format:Y-m-d H:i:s'
        ]);
        $req->merge(['description' => htmlspecialchars($req->input('description'))]);

        // store thumbnail
        $thumbnailPath = Location::DEFAULT_THUMBNAIL;
        if ($req->hasFile('thumbnail')) {
            $thumbnail = $req->file('thumbnail');
            $thumbnailPath = $thumbnail->store('locations/thumbnails', ['disk' => 'public']);
        }
        $location = new Location($req->only([
            'name',
            'name_local',
            'url_location',
            'short_description',
            'description',
            'lat',
            'lot',
            'category_id',
            'province_id',
            'district_id',
            'commune_id',
            'village_id'
        ]));
        $location->thumbnail = $thumbnailPath;
        if ($req->filled('published_at'))  $location->published_at = $req->input('published_at');
        $user = UserService::getAuthUser($req);
        $location->create_uid = $user->id;
        $location->update_uid = $user->id;
        $location->save();
        $location->tags()->sync($req->input('tag_ids'));
        return res_success('Store location successful.', new LocationDetailResource($location));
    }
    public function index(Request $req)
    {
        // validation
        $req->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'sort_col' => 'nullable|string|in:id,total_view',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'search' => 'nullable|string|max:50',
            'category' => 'nullable|integer|min:1|exists:categories,id,is_deleted,0',
            'province' => 'nullable|integer|min:1|exists:provinces,id',
            'district' => 'nullable|integer|min:1|exists:districts,id,is_deleted,0',
            'commune' => 'nullable|integer|min:1|exists:communes,id,is_deleted,0',
            'village' => 'nullable|integer|min:1|exists:villages,id'
        ]);

        // setup default data
        $perPage = $req->filled('per_page') ? intval($req->input('per_page')) : 10;
        $sortCol = $req->filled('sort_col') ? $req->input('sort_col') : 'id';
        $sortDir = $req->filled('sort_dir') ? $req->input('sort_dir') : 'desc';

        // add search
        $locations = new Location();
        $locations = $locations->where('is_deleted', 0);
        if ($req->filled('search')) {
            $s = $req->input('search');
            $locations = $locations->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                    ->orWhere('name_local', 'like', "%$s%")
                    ->orWhere('short_description', 'like', "%$s%")
                    ->orWhere('description', 'like', "%$s%");
            });
        }

        if ($req->filled('category')) {
            $category = intval($req->input('category'));
            $locations = $locations->where('category_id', $category);
        }
        if ($req->filled('province')) {
            $province = intval($req->input('province'));
            $locations = $locations->where('province_id', $province);
        }
        if ($req->filled('district')) {
            $district = intval($req->input('district'));
            $locations = $locations->where('district_id', $district);
        }
        if ($req->filled('commune')) {
            $commune = intval($req->input('commune'));
            $locations = $locations->where('commune_id', $commune);
        }
        if ($req->filled('village')) {
            $village = intval($req->input('village'));
            $locations = $locations->where('village_id', $village);
        }

        $locations = $locations->whereNotNull('published_at')->with(['tags', 'category', 'province'])->withAvg('stars', 'star')->orderBy($sortCol, $sortDir)->paginate($perPage);
        return res_paginate($locations, 'Get locations successful.', LocationIndexResource::collection($locations));
    }

    public function find(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:locations,id,is_deleted,0']);

        // get location by id
        $location = Location::where('id', $id)
            ->where('is_deleted', 0)
            ->with(['tags', 'category', 'province', 'district', 'commune', 'village', 'stars', 'stars.rater', 'photos'])
            ->withAvg('stars', 'star')
            ->whereNotNull('published_at')
            ->first();
        if (!$location) {
            return res_fail('Location is not publish yet or not found', [], 1, 404);
        }

        // response back
        return res_success('Get one location success', new LocationDetailResource($location));
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id, 'tag_ids' => json_decode($req->input('tag_ids')) ?? []]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:locations,id,is_deleted,0',
            'name' => 'required|string|max:250',
            'name_local' => 'required|string|max:250',
            'thumbnail' => 'nullable|image|mimetypes:image/png,image/jpeg|max:2048',
            'url_location' => 'nullable|url',
            'short_description' => 'nullable|string|max:250',
            'description' => 'nullable|string|max:65530',
            'lat' => 'nullable|numeric|min:0',
            'lot' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|integer|min:1|exists:categories,id,is_deleted,0',
            'province_id' => 'nullable|integer|min:1|exists:provinces,id',
            'district_id' => 'nullable|integer|min:1|exists:districts,id,is_deleted,0',
            'commune_id' => 'nullable|integer|min:1|exists:communes,id,is_deleted,0',
            'village_id' => 'nullable|integer|min:1|exists:villages,id',
            'tag_ids' => 'required|array|min:0|max:5',
            'tag_ids.*' => 'integer|min:1|exists:tags,id',
        ]);
        $location = Location::where('id', $id)->where('is_deleted', 0)->first();
        if (!$location) return res_fail('Location not found.', [], 1, 404);

        // Set user info
        $user = UserService::getAuthUser($req);
        $location->update_uid = $user->id;

        // update name
        if ($req->filled('name')) {
            $location->name = $req->input('name');
        }
        if ($req->filled('name_local')) {
            $location->name_local = $req->input('name_local');
        }

        // update thumbnail
        if ($req->hasFile('thumbnail')) {
            $thumbnailPath = $req->file('thumbnail')->store('locations/thumbnails', ['disk' => 'public']);
            if ($location->thumbnail != Location::DEFAULT_THUMBNAIL) {
                Storage::disk('public')->delete($location->thumbnail);
            }
            $location->thumbnail = $thumbnailPath;
        }

        // update url
        if ($req->filled('url_location')) {
            $location->url_location = $req->input('url_location');
        }

        // update description
        if ($req->filled('short_description')) {
            $location->short_description = $req->input('short_description');
        }
        if ($req->filled('description')) {
            $location->description = htmlspecialchars($req->input('description'));
        }

        // update lat log
        if ($req->filled('lat')) {
            $location->lat = $req->input('lat');
        }
        if ($req->filled('lot')) {
            $location->lot = $req->input('lot');
        }

        // update category
        if ($req->filled('category_id')) {
            $location->category_id = $req->input('category_id');
        }

        // update address
        if ($req->filled('province_id')) {
            $location->province_id = $req->input('province_id');
        }
        if ($req->filled('district_id')) {
            $location->district_id = $req->input('district_id');
        }
        if ($req->filled('commune_id')) {
            $location->commune_id = $req->input('commune_id');
        }
        if ($req->filled('village_id')) {
            $location->village_id = $req->input('village_id');
        }

        // save location & check tags
        $location->save();
        $location->tags()->sync($req->input('tag_ids'));
        return res_success('Update location successful', new LocationDetailResource($location));
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:locations,id,is_deleted,0'
        ]);

        // find location
        $location = Location::where('id', $id)->where('is_deleted', 0)->with('photos')->first();
        if (!$location) return res_fail('Location not found.', [], 1, 404);

        // Before soft delete, handle any needed cleanup
        if ($location->thumbnail != Location::DEFAULT_THUMBNAIL) {
            Storage::disk('public')->delete($location->thumbnail);
            $location->thumbnail = Location::DEFAULT_THUMBNAIL;
        }

        // Soft delete
        $user = UserService::getAuthUser($req);
        $location->update([
            'is_deleted' => 1,
            'deleted_uid' => $user->id,
            'deleted_datetime' => now()
        ]);
        return res_success('Delete location successful.');
    }

    public function addToWishlist(Request $req)
    {
        $req->validate([
            'location_id' => 'required|integer|exists:locations,id,is_deleted,0'
        ]);

        $user = UserService::getAuthUser($req);
        $locationId = $req->location_id;
        $location = Location::where('is_deleted', 0)->find($locationId);
        if (!$location) {
            return res_fail('Location not found.', [], 1, 404);
        }
        WishlistItem::updateOrCreate(
            [
                'user_id' => $user->id,
                'location_id' => $locationId
            ]
        );
        return res_success('Location added to wishlist successfully.');
    }


    public function removeFromWishlist(Request $req)
    {
        $req->validate([
            'location_id' => 'required|integer|exists:locations,id'
        ]);
        $user = UserService::getAuthUser($req);
        $locationId = $req->location_id;
        $deleted = WishlistItem::where('user_id', $user->id)
            ->where('location_id', $locationId)
            ->delete();
        if ($deleted) {
            return res_success('Location removed from wishlist successfully.');
        } else {
            return res_fail('Location not found in wishlist.', [], 1, 404);
        }
    }

    public function getWishlist(Request $req)
    {
        $user = UserService::getAuthUser($req);
        $wishlistLocations = WishlistItem::with(
            'user',
            'location',
            'location.province:id,name',
            'location.district:id,name',
            'location.commune:id,name',
            'location.village:id,name',

        )
            ->where('user_id', $user->id)
            ->selectRaw('id,location_id,user_id')
            ->get();
        foreach ($wishlistLocations as $wishlist) {
            $wishlist->creator = $wishlist->user->first_name . ' ' . $wishlist->user->last_name ?? '';
            $wishlist->location_name = $wishlist->location->name ?? '';
            $wishlist->location_km = $wishlist->location->name_local ?? '';
            $wishlist->short_description = $wishlist->location->short_description ?? '';
            $wishlist->description = $wishlist->location->description ?? '';
            $wishlist->url_location = $wishlist->location->url_location ?? '';
            $wishlist->published_at = $wishlist->location->published_at ?? '';
            $wishlist->min_money = $wishlist->location->min_money ?? '';
            $wishlist->max_money = $wishlist->location->max_money ?? '';
            $wishlist->lat = $wishlist->location->lat ?? '';
            $wishlist->lot = $wishlist->location->lot ?? '';
            $wishlist->thumbnail = asset("storage/{$wishlist->location->thumbnail}");
            $wishlist->total_view = $wishlist->location->total_view ?? '';
            $wishlist->status = $wishlist->location->status ?? '';
            unset(
                $wishlist->user,
                $wishlist->location,
            );
        }
        return res_success('Wishlist retrieved successfully.', $wishlistLocations);
    }

    // Add this method to check if a location is in the user's wishlist
    public function isInWishlist(Request $req, $locationId)
    {
        $user = UserService::getAuthUser($req);
        $exists = WishlistItem::where('user_id', $user->id)
            ->where('location_id', $locationId)
            ->exists();
        return res_success('Wishlist status retrieved.', [
            'in_wishlist' => $exists
        ]);
    }
}
