<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class SiemReapController extends Controller
{
    public function getSiemReapLists(Request $req)
    {
        $req->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
            'district_id' => 'nullable|integer|exists:districts,id',
            'commune_id' => 'nullable|integer|exists:communes,id',
            'village_id' => 'nullable|integer|exists:villages,id',
            'star' => 'nullable|numeric|min:0|max:5',
            'min_total_view' => 'nullable|integer|min:0',
        ]);
        $page = $req->filled('page') ? intval($req->input('page')) : 1;
        $perPage = $req->filled('per_page') ? intval($req->input('per_page')) : 10;
        $locationsQuery = Location::where('is_deleted', 0)
            ->where('status', 1)
            ->where(function($query) {
                $query->where('province_id', 8)
                      ->orWhere('category_id', 4);
            })
            ->with([
                'stars:id,star,location_id',
                'province:id,name,local_name',
                'district:id,name,local_name',
                'commune:id,name,local_name',
                'village:id,name,local_name',
                'category:id,name',
                'category.products:id,category_id,name,name_km,description,thumbnail',
            ])
            ->orderBy('id', 'desc')
            ->take(100)
            ->select(
                'id',
                'name',
                'name_local',
                'thumbnail',
                'short_description',
                'total_view',
                'province_id',
                'district_id',
                'commune_id',
                'village_id',
                'category_id'
            );
        if ($req->filled('district_id')) {
            $locationsQuery->where('district_id', $req->input('district_id'));
        }
        if ($req->filled('commune_id')) {
            $locationsQuery->where('commune_id', $req->input('commune_id'));
        }
        if ($req->filled('village_id')) {
            $locationsQuery->where('village_id', $req->input('village_id'));
        }
        if ($req->filled('min_total_view')) {
            $locationsQuery->where('total_view', '>=', $req->input('min_total_view'));
        }
        $locations = $locationsQuery->get();
        $productRows = [];
        foreach ($locations as $location) {
            $location->rate_star = round($location->stars->avg('star'), 2) ?? 0;
            $location->is_thumbnail = asset("storage/{$location->thumbnail}");
            if ($location->category && $location->category->products) {
                foreach ($location->category->products as $product) {
                    $product->thumbnail_url = asset("storage/{$product->thumbnail}");
                    foreach ($product->variants ?? [] as $variant) {
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
                }
            }
            unset(
                $location->stars,
                $location->thumbnail,
                $location->province_id,
                $location->commune_id,
                $location->district_id,
                $location->village_id,
                $location->category->products
            );
        }
        if ($req->filled('star')) {
            $minStar = floatval($req->input('star'));
            $locations = $locations->filter(function ($location) use ($minStar) {
                return $location->rate_star >= $minStar;
            })->values();
        }
        $totalLocations = $locations->count();
        $offset = ($page - 1) * $perPage;
        $paginatedLocations = $locations->slice($offset, $perPage)->values();
        $topViewLocation = $locations->sortByDesc('total_view')->values();
        $paginatedTopViewLocations = $topViewLocation->slice($offset, $perPage)->values();
        $pagination = [
            'total' => $totalLocations,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($totalLocations / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $totalLocations),
        ];
        return res_success("Get success home page", [
            'top_view_location' => $paginatedTopViewLocations,
            'locations' => $paginatedLocations,
            'product_relates' => $productRows,
            'pagination' => $pagination,
        ]);
    }
}
