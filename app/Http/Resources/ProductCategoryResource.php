<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class ProductCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "name_km" => $this->name_km,
            'created_at' => Carbon::parse($this->created_at)->format('d/m/Y'),
            "updated_at" => Carbon::parse($this->created_at)->format('d/m/Y'),
        ];
    }
}
