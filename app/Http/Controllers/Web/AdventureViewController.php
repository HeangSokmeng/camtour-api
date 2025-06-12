<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class AdventureViewController extends Controller
{
    public function getAdventure(Request $req)
    {
        $req->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
            'province_id' => 'nullable|integer|exists:provinces,id',
            'district_id' => 'nullable|integer|exists:districts,id',
            'commune_id' => 'nullable|integer|exists:communes,id',
            'village_id' => 'nullable|integer|exists:villages,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'star' => 'nullable|numeric|min:0|max:5',
            'search' => 'nullable|string|max:255',
            'lang' => 'nullable|string|in:en,km',
        ]);
        $page = $req->filled('page') ? intval($req->input('page')) : 1;
        $perPage = $req->filled('per_page') ? intval($req->input('per_page')) : 10;
        $search = $req->input('search');
        $locationsQuery = Location::where('is_deleted', 0)
            ->with([
                'stars:id,star,location_id',
                'province:id,name,local_name',
                'district:id,name,local_name',
                'commune:id,name,local_name',
                'village:id,name,local_name',
                'category:id,name,name_km',
            ])
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->select([
                'id',
                'name',
                'name_local',
                'thumbnail',
                'url_location',
                'short_description',
                'description',
                'total_view',
                'province_id',
                'district_id',
                'commune_id',
                'village_id',
                'category_id'
            ]);

        // Apply search filter
        if ($search) {
            $locationsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('name_local', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($req->filled('province_id')) {
            $locationsQuery->where('province_id', $req->input('province_id'));
        }
        if ($req->filled('district_id')) {
            $locationsQuery->where('district_id', $req->input('district_id'));
        }
        if ($req->filled('commune_id')) {
            $locationsQuery->where('commune_id', $req->input('commune_id'));
        }
        if ($req->filled('village_id')) {
            $locationsQuery->where('village_id', $req->input('village_id'));
        }
        if ($req->filled('category_id')) {
            $locationsQuery->where('category_id', $req->input('category_id'));
        }
        $locations = $locationsQuery->take(1000)->get();
        foreach ($locations as $location) {
            $location->rate_star = $location->stars->count() > 0
                ? round($location->stars->avg('star'), 2)
                : 0;
            $location->is_thumbnail = $location->thumbnail
                ? asset("storage/{$location->thumbnail}")
                : null;
            unset(
                $location->stars,
                $location->thumbnail,
                $location->province_id,
                $location->commune_id,
                $location->district_id,
                $location->village_id,
                $location->category_id
            );
        }
        if ($req->filled('star')) {
            $minStar = floatval($req->input('star'));
            $locations = $locations->filter(function ($location) use ($minStar) {
                return $location->rate_star >= $minStar;
            })->values();
        }
        $topViewLocation = $locations->sortByDesc('total_view')->take(20)->values();
        $totalLocations = $locations->count();
        $offset = ($page - 1) * $perPage;
        $paginatedLocations = $locations->slice($offset, $perPage)->values();
        $pagination = [
            'total' => $totalLocations,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($totalLocations / $perPage),
            'from' => $totalLocations > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $totalLocations),
        ];
        return res_success("Get success adventure page", [
            'top_view_location' => $topViewLocation,
            'locations' => $paginatedLocations,
            'pagination' => $pagination,
        ]);
    }


    public function getLocationStats(Request $request)
    {
        $totalLocations = Location::where('is_deleted', 0)
            ->where('status', 1)
            ->count();
        $locationsByProvince = Location::where('is_deleted', 0)
            ->where('status', 1)
            ->with('province:id,name,local_name')
            ->select('province_id')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('province_id')
            ->get();
        $locationsByCategory = Location::where('is_deleted', 0)
            ->where('status', 1)
            ->with('category:id,name,local_name')
            ->select('category_id')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('category_id')
            ->get();
        return res_success("Location statistics retrieved successfully", [
            'total_locations' => $totalLocations,
            'by_province' => $locationsByProvince,
            'by_category' => $locationsByCategory,
        ]);
    }
}
