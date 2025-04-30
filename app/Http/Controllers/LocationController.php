<?php

namespace App\Http\Controllers;

use App\Http\Resources\LocationDetailResource;
use App\Http\Resources\LocationIndexResource;
use App\Models\Location;
use App\Models\LocationImage;
use Illuminate\Http\Request;
use Storage;

class LocationController extends Controller
{
    public function store(Request $req)
    {
        // validation
        $req->merge(['tag_ids' => json_decode($req->input('tag_ids')) ?? []]);
        $req->validate([
            'name' => 'required|string|max:250|unique:locations,name',
            'name_local' => 'required|string|max:250|unique:locations,name_local',
            'thumbnail' => 'nullable|image|mimetypes:image/png,image/jpeg|max:2048',
            'url_location' => 'nullable|url',
            'short_description' => 'nullable|string|max:250',
            'description' => 'nullable|string|max:65530',
            'lat' => 'nullable|numeric|min:0',
            'lot' => 'nullable|numeric|min:0',
            'category_id' => 'required|integer|min:1|exists:categories,id',
            'province_id' => 'required|integer|min:1|exists:provinces,id',
            'district_id' => 'required|integer|min:1|exists:districts,id',
            'commune_id' => 'required|integer|min:1|exists:communes,id',
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

        // store location
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
        if ($req->filled('published_at')) {
            $location->published_at = $req->input('published_at');
        }
        $location->save();

        // store location tag & response
        $location->tags()->sync($req->input('tag_ids'));
        return res_success('Store location successful.');
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
            'category' => 'nullable|integer|min:1|exists:categories,id',
            'province' => 'nullable|integer|min:1|exists:provinces,id',
            'district' => 'nullable|integer|min:1|exists:districts,id',
            'commune' => 'nullable|integer|min:1|exists:commune,id',
            'village' => 'nullable|integer|min:1|exists:villages,id'
        ]);

        // setup default data
        $perPage = $req->filled('per_page') ? intval($req->input('per_page')) : 10;
        $sortCol = $req->filled('sort_col') ? $req->input('sort_col') : 'id';
        $sortDir = $req->filled('sort_dir') ? $req->input('sort_dir') : 'desc';

        // add search
        $locations = new Location();
        if ($req->filled('search')) {
            $s = $req->input('search');
            $locations = $locations->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                    ->orWhere('name_local', 'like', "%$s%")
                    ->orWhere('short_description', 'like', "%$s%")
                    ->orWhere('description', 'like', "%$s%");
            });
        }

        // add option filter category
        if ($req->filled('category')) {
            $category = intval($req->input('category'));
            $locations = $locations->where('category_id', $category);
        }

        // add option filter province
        if ($req->filled('province')) {
            $province = intval($req->input('province'));
            $locations = $locations->where('province_id', $province);
        }

        // add option filter district
        if ($req->filled('district')) {
            $district = intval($req->input('district'));
            $locations = $locations->where('district_id', $district);
        }

        // add option filter commune
        if ($req->filled('commune')) {
            $commune = intval($req->input('commune'));
            $locations = $locations->where('commune_id', $commune);
        }

        // add option filter village
        if ($req->filled('village')) {
            $village = intval($req->input('village'));
            $locations = $locations->where('village_id', $village);
        }

        // add sortable & get data
        $locations = $locations->whereNotNull('published_at')->with(['tags', 'category', 'province'])->withAvg('stars', 'star')->orderBy($sortCol, $sortDir)->paginate($perPage);
        return res_paginate($locations, 'Get locations successful.', LocationIndexResource::collection($locations));
    }

    public function find(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:locations,id']);

        // get location by id
        $location = Location::where('id', $id)
            ->with(['tags', 'category', 'province', 'district', 'commune', 'village', 'stars', 'stars.rater', 'photos'])
            ->withAvg('stars', 'star')
            ->whereNotNull('published_at')
            ->first();
        if (!$location) {
            return res_fail('Location is not publish yet');
        }

        // response back
        return res_success('Get one location success', new LocationDetailResource($location));
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id, 'tag_ids' => json_decode($req->input('tag_ids')) ?? []]);
        $req->validate([
            'id'=> 'required|integer|min:1|exists:locations,id',
            'name' => 'nullable|string|max:250|unique:locations,name,' . $id,
            'name_local' => 'nullable|string|max:250|unique:locations,name_local,' . $id,
            'thumbnail' => 'nullable|image|mimetypes:image/png,image/jpeg|max:2048',
            'url_location' => 'nullable|url',
            'short_description' => 'nullable|string|max:250',
            'description' => 'nullable|string|max:65530',
            'lat' => 'nullable|numeric|min:0',
            'lot' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|integer|min:1|exists:categories,id',
            'province_id' => 'nullable|integer|min:1|exists:provinces,id',
            'district_id' => 'nullable|integer|min:1|exists:districts,id',
            'commune_id' => 'nullable|integer|min:1|exists:communes,id',
            'village_id' => 'nullable|integer|min:1|exists:villages,id',
            'tag_ids' => 'required|array|min:0|max:5',
            'tag_ids.*' => 'integer|min:1|exists:tags,id',
        ]);
        $location = Location::where('id', $id)->first();

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
        $location->tags()->sync($req->input('tags'));
        return res_success('Update location successful');
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:locations,id'
        ]);

        // delete thumbnail & photo
        $location = Location::where('id', $id)->with('photos')->first(['id', 'thumbnail']);
        if ($location->thumbnail != Location::DEFAULT_THUMBNAIL) {
            Storage::disk('public')->delete($location->thumbnail);
        }
        foreach ($location->photos as $photo) {
            Storage::disk('public')->delete($photo->photo);
        }
        $location->delete();
        return res_success('Delete location successful.');
    }
}
