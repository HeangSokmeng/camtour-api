<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_local' => $this->name_local,
            'thumbnail' => asset("storage/{$this->thumbnail}"),
            'short_description' => $this->short_description,
            'url_location' => $this->url_location,
            'total_view' => $this->total_view,
            'published_at' => $this->published_at ? Carbon::parse($this->published_at)->format('Y-m-d H:i:s') : null,
            'category' => new CategoryResource($this->category),
            'province' => new ProvinceResource($this->province),
            'tags' => TagResource::collection($this->tags),
            'avg_star' => floatval(number_format($this->stars_avg_star ?: 0, 2))
        ];
    }
}
