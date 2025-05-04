<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_km' => $this->name_km,
            'created_at' => Carbon::parse($this->created_at)->format('d/m/Y'),
            "updated_at" => Carbon::parse($this->created_at)->format('d/m/Y'),
        ];
    }
}
