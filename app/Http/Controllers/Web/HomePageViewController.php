<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\LocationDetailResource;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Http\Request;

class HomePageViewController extends Controller
{
    public function getLocationAndProduct(Request $req)
    {
        $req->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:categories,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'province_id' => 'nullable|integer|exists:provinces,id',
            'district_id' => 'nullable|integer|exists:districts,id',
            'commune_id' => 'nullable|integer|exists:communes,id',
            'village_id' => 'nullable|integer|exists:villages,id',
            'province' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'commune' => 'nullable|string|max:255',
            'village' => 'nullable|string|max:255',
        ]);

        $page = $req->input('page', 1);
        $perPage = $req->input('per_page', 10);
        $search = $req->input('search');
        $category = $req->input('category');
        $categoryId = $req->input('category_id');
        $brandId = $req->input('brand_id');
        $provinceId = $req->input('province_id');
        $districtId = $req->input('district_id');
        $communeId = $req->input('commune_id');
        $villageId = $req->input('village_id');
        $province = $req->input('province');
        $district = $req->input('district');
        $commune = $req->input('commune');
        $village = $req->input('village');

        $locationQuery = Location::where('is_deleted', 0)
            ->with([
                'stars:id,star,location_id',
                'category:id,name',
                'province:id,name',
                'district:id,name',
                'commune:id,name',
                'village:id,name'
            ])
            ->where('status', 1);

        if ($search) {
            $locationQuery->where(function ($query) use ($search) {
                $query->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('name_local', 'ILIKE', "%{$search}%")
                    ->orWhere('short_description', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        if ($categoryId) {
            $locationQuery->where('category_id', $categoryId);
        } elseif ($category) {
            $locationQuery->whereHas('category', function ($query) use ($category) {
                $query->where('name', 'ILIKE', "%{$category}%");
            });
        }

        if ($provinceId) {
            $locationQuery->where('province_id', $provinceId);
        }

        if ($province) {
            $locationQuery->whereHas('province', function ($query) use ($province) {
                $query->where('name', 'ILIKE', "%{$province}%");
            });
        }

        if ($districtId) {
            $locationQuery->where('district_id', $districtId);
        }

        if ($district) {
            $locationQuery->whereHas('district', function ($query) use ($district) {
                $query->where('name', 'ILIKE', "%{$district}%");
            });
        }

        if ($communeId) {
            $locationQuery->where('commune_id', $communeId);
        }

        if ($commune) {
            $locationQuery->whereHas('commune', function ($query) use ($commune) {
                $query->where('name', 'ILIKE', "%{$commune}%");
            });
        }

        if ($villageId) {
            $locationQuery->where('village_id', $villageId);
        }

        if ($village) {
            $locationQuery->whereHas('village', function ($query) use ($village) {
                $query->where('name', 'ILIKE', "%{$village}%");
            });
        }

        $latestLocations = $locationQuery
            ->orderBy('id', 'desc')
            ->limit(8)
            ->select('id', 'name', 'name_local', 'thumbnail', 'url_location', 'short_description', 'description', 'total_view', 'category_id', 'province_id', 'district_id', 'commune_id', 'village_id')
            ->get();

        foreach ($latestLocations as $location) {
            $location->rate_star = round($location->stars->avg('star'), 2) ?? 0;
            $location->is_thumbnail = asset("storage/{$location->thumbnail}");
            unset($location->stars, $location->thumbnail);
        }

        $totalLocations = $latestLocations->count();
        $offset = ($page - 1) * $perPage;
        $paginatedLocations = $latestLocations->slice($offset, $perPage)->values();
        $topViewLocation = $latestLocations->sortByDesc('total_view')->values();
        $paginatedTopViewLocations = $topViewLocation->slice($offset, $perPage)->values();

        $productQuery = Product::where('is_deleted', 0)
            ->selectRaw('id,name,name_km,description,thumbnail,brand_id');

        if ($brandId) {
            $productQuery->where('brand_id', $brandId);
        }

        if ($search) {
            $productQuery->where(function ($query) use ($search) {
                $query->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('name_km', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        $products = $productQuery->with([
            'variants' => function ($query) {
                $query->where('is_deleted', 0)
                    ->select('id', 'product_id', 'product_color_id', 'product_size_id', 'qty', 'price');
            },
            'variants.color:id,name,code',
            'variants.size:id,size',
            'brand:id,name,name_km'
        ])->get();

        $productRows = [];
        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                $productRows[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_name_km' => $product->name_km,
                    'is_thumbnail' => asset("storage/{$product->thumbnail}"),
                    'color' => $variant->color->name ?? null,
                    'size' => $variant->size->size ?? null,
                    'qty' => $variant->qty,
                    'price' => $variant->price,
                    'brand_id' => $product->brand_id,
                    'brand' => $product->brand ? $product->brand->name : null,
                    'brand_km' => $product->brand ? $product->brand->name_km : null,
                ];
            }
        }

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
            'product' => $productRows,
            'pagination' => $pagination,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'province_id' => $provinceId,
                'district_id' => $districtId,
                'commune_id' => $communeId,
                'village_id' => $villageId,
                'province' => $province,
                'district' => $district,
                'commune' => $commune,
                'village' => $village,
            ]
        ]);
    }


    public function find(Request $req, $id)
    {
        $req->merge(['id' => $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:locations,id,is_deleted,0']);
        $location = Location::where('is_deleted', 0)->where('id', $id)
            ->where('is_deleted', 0)
            ->with(['tags', 'category', 'province', 'district', 'commune', 'village', 'stars', 'stars.rater', 'photos'])
            ->withAvg('stars', 'star')
            ->whereNotNull('published_at')
            ->first();
        if (!$location)  return res_fail('Location is not publish yet or not found', [], 1, 404);
        $location->increment('total_view');
        return res_success('Get one location success', new LocationDetailResource($location));
    }
}
