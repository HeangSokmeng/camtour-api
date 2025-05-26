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
    ]);

    $page = $req->input('page', 1);
    $perPage = $req->input('per_page', 10);
    $search = $req->input('search');
    $category = $req->input('category');

    // Build location query with search functionality
    $locationQuery = Location::where('is_deleted', 0)
        ->with('stars:id,star,location_id')
        ->where('status', 1);

    // Apply location name search
    if ($search) {
        $locationQuery->where(function ($query) use ($search) {
            $query->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('name_local', 'LIKE', "%{$search}%")
                  ->orWhere('short_description', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }

    $latestLocations = $locationQuery
        ->orderBy('id', 'desc')
        ->limit(20)
        ->select('id', 'name', 'name_local', 'thumbnail', 'url_location', 'short_description', 'description', 'total_view')
        ->get();

    // Process locations
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

    // Build product query with search functionality
    $productQuery = Product::where('is_deleted', 0)
        ->selectRaw('id,name,name_km,description,thumbnail');

    // Apply category filter (excluding Siem Reap by default)
    $productQuery->whereHas('category', function ($query) use ($category) {
        if ($category) {
            $query->where('name', 'LIKE', "%{$category}%");
        } else {
            $query->where('name', '!=', 'Siem Reap');
        }
    });

    // Apply product search
    if ($search) {
        $productQuery->where(function ($query) use ($search) {
            $query->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('name_km', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }

    $products = $productQuery->with([
        'variants' => function ($query) {
            $query->where('is_deleted', 0)
                ->select('id', 'product_id', 'product_color_id', 'product_size_id', 'qty', 'price');
        },
        'variants.color:id,name,code',
        'variants.size:id,size'
    ])->get();

    // Process products
    $productRows = [];
    foreach ($products as $product) {
        foreach ($product->variants as $variant) {
            $productRows[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'is_thumbnail' => asset("storage/{$product->thumbnail}"),
                'color' => $variant->color->name ?? null,
                'size' => $variant->size->size ?? null,
                'qty' => $variant->qty,
                'price' => $variant->price,
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
