<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_local' => $this->name_local,
            'thumbnail' => asset("storage/{$this->thumbnail}"),
            'short_description' => $this->short_description,
            'description' => htmlspecialchars_decode($this->description),
            'url_location' => $this->url_location,
            'total_view' => $this->total_view,
            'lat' => $this->lat,
            'lot' => $this->lot,
            'published_at' => $this->published_at ? Carbon::parse($this->published_at)->format('Y-m-d H:i:s') : null,
            'avg_star' => floatval(number_format($this->stars_avg_star ?: 0, 2)),
            'category' => new CategoryResource($this->category),
            'province' => [
                'id' => $this->province->id,
                'name' => $this->province->name,
                'local_name' => $this->province->local_name,
            ],
            'district' => [
                'id' => $this->district->id,
                'name' => $this->district->name,
                'local_name' => $this->district->local_name,
            ],
            'commune' => [
                'id' => $this->commune->id,
                'name' => $this->commune->name,
                'local_name' => $this->commune->local_name,
            ],
            'village' => [
                'id' => $this->village->id,
                'name' => $this->village->name,
                'local_name' => $this->village->local_name,
            ],
            'tags' => TagResource::collection($this->tags),
            'stars' => LocationStarResource::collection($this->stars),
            'photos' => LocationImageResource::collection($this->photos),
        ];
    }
}
