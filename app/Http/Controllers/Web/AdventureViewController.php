<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Http\Request;

class AdventureViewController extends Controller
{
    public function getAdventure(Request $req)
    {
        $req->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
            'province_id' => 'nullable|integer|exists:provinces,id',
            'star' => 'nullable|numeric|min:0|max:5',
        ]);

        $page = $req->filled('page') ? intval($req->input('page')) : 1;
        $perPage = $req->filled('per_page') ? intval($req->input('per_page')) : 10;

        $locationsQuery = Location::where('is_deleted', 0)
            ->with(
                'stars:id,star,location_id',
                'province:id,name,local_name',
                'district:id,name,local_name',
                'commune:id,name,local_name',
                'village:id,name,local_name',
                'category:id,name',
            )
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->take(100)
            ->select(
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
            );

        // Filter by province
        if ($req->filled('province_id')) {
            $locationsQuery->where('province_id', $req->input('province_id'));
        }

        // Fetch locations and apply star filter after loading stars
        $locations = $locationsQuery->get();

        // Calculate average star and prepare the data
        foreach ($locations as $location) {
            $location->rate_star = round($location->stars->avg('star'), 2) ?? 0;
            $location->is_thumbnail = asset("storage/{$location->thumbnail}");
            unset(
                $location->stars,
                $location->thumbnail,
                $location->province_id,
                $location->commune_id,
                $location->district_id,
                $location->village_id,
            );
        }

        // Filter by star (after calculating average star)
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
            'pagination' => $pagination,
        ]);
    }
}
