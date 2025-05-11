<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\LocationImage;
use Illuminate\Http\Request;

class LocationDetailController extends Controller
{
    public function getOneLocationView(Request $req)
    {
        $id = $req->id;
        $location = Location::query()
            ->with([
                'photos:id,location_id,photo',
                'tags:id,name',
                'province:id,name,local_name',
                'district:id,name,local_name',
                'commune:id,name,local_name',
                'village:id,name,local_name',
                'category:id,name',
                'stars' => function ($query) {
                    $query->select('id', 'location_id', 'star', 'comment', 'rater_id')
                        ->orderBy('id', 'desc');
                },
                'category.products:id,category_id,name,name_km,description,thumbnail',
                'category.products.variants:id,product_id,product_color_id,product_size_id,qty,price',
                'category.products.variants.color:id,name,code',
                'category.products.variants.size:id,size',
            ])
            ->where('is_deleted', 0)
            ->find($id);
        if (!$location)  return res_fail("Location not found", 404);
        $location->is_thumbnail = asset("storage/{$location->thumbnail}");
        foreach ($location->photos as $photo) {
            $photo->photo_url = asset("storage/{$photo->photo}");
            unset($photo->photo);
        }
        $productRows = [];
        foreach ($location->category->products as $product) {
            $product->thumbnail_url = asset("storage/{$product->thumbnail}");
            foreach ($product->variants as $variant) {
                $productRows[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'is_thumbnail' => $product->thumbnail_url,
                    'color' => $variant->color->name ?? null,
                    'size' => $variant->size->size ?? null,
                    'qty' => $variant->qty,
                    'price' => $variant->price,
                ];
            }
            unset($product->thumbnail, $location->category->products);
        }
        $location->stars->each(function ($star) {
            $star->rater_name = optional($star->rater)->first_name . ' ' . optional($star->rater)->last_name;
            unset($star->rater);
        });

        $location->products = $productRows;
        $location->increment('total_view');
        // $location->stars->increment('total_view');
        return res_success("Get detail location", $location);
    }
}
