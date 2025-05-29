<?php

namespace App\Http\Resources\Product;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductColorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "code" => $this->code,
            "product" => $this->product,
            "product_id" => $this->product_id,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
