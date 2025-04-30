<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "name_km" => $this->name_km,
            "code" => $this->code,
            "thumbnail" => asset("storage/{$this->thumbnail}"),
            "price" => floatval($this->price)
        ];
    }
}
