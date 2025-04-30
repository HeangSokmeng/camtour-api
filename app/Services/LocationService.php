<?php

namespace App\Services;

use ApiResponse;
use App\Models\Commune;
use App\Models\District;
use App\Models\Province;
use App\Models\Village;
use Illuminate\Http\Request;

/**
 * Class LocationService.
 */
class LocationService
{

    public static function locationValidation(Request $req)
    {
        return validator($req->all(), [
            'name' => 'required|string|max:100',
            'name_local' => 'nullable|string|max:150',
            'url_location' => 'nullable|string',
            'description' => 'nullable|string',
            'sort_description' => 'nullable|string|max:150',
            'rate' => 'nullable|numeric',
            'total_view' => 'nullable|integer',
            'lat' => 'nullable|numeric',
            'lot' => 'nullable|numeric',
            'tag_id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
            'location_id' => 'nullable',
            'province_id' => 'nullable',
            'district_id' => 'nullable',
            'commune_id' => 'nullable',
            'village_id' => 'nullable',
            'photos' => 'nullable|array',
        ]);
    }

    public static function locationPhotos(Request $req)
    {
        return validator($req->all(), [
            'locationId' => 'nullable',
            'photo' => 'required|string',
            'is_thumbnail' => 'nullable|boolean'
        ], [
            'photo.required' => 'Image is empty, Please add the image'
        ]);
    }
}
