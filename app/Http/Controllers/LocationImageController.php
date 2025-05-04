<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\LocationImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LocationImageController extends Controller
{
    public function storeImage(Request $req, $id)
    {
        // Merge ID into request for validation
        $req->merge(['id' => $id]);

        // Validate input
        $req->validate([
            'id' => 'required|integer|min:1|exists:locations,id',
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $images = $req->file('images');

        // Check if images are uploaded
        if (!$images || !is_array($images)) {
            return false;
        }

        // Store each image and save record
        foreach ($images as $image) {
            $imagePath = $image->store('locations/photos', ['disk' => 'public']);

            $locationImage = new LocationImage([
                'location_id' => $id,
                'photo' => $imagePath,
            ]);
            $locationImage->save();
        }

        return res_success('Add images successful.');
    }


    public function destroy(Request $req, $imageId)
    {
        // validation
        $req->merge(['id' => $imageId]);
        $req->validate(['id' => 'required|integer|min:1|exists:location_images,id']);

        // delete image file
        $image = LocationImage::where('id', $imageId)->first(['id', 'photo']);
        Storage::disk('public')->delete($image->photo);
        $image->delete();

        // response back
        return res_success('Delete photo successful');
    }

    public function getImages($id)
    {
        $location = Location::with('photos')->find($id);

        if (!$location) {
            return res_fail('Location not found.', 404);
        }

        // Format image URLs
        $images = $location->images->map(function ($img) {
            return [
                'id' => $img->id,
                'photo' => $img->photo,
                'url' => asset('storage/' . $img->photo)
            ];
        });

        return res_success('Images fetched successfully.', $images);
    }
}
