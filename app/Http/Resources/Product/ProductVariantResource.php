<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "qty" => $this->qty,
            "size" => $this->size->size,
            "price" => $this->price,
            "color_name" => $this->color->name,
            "color_code" => $this->color->code,
            "product" => $this->product->name,
            "brand" => $this->product->brand->name,
        ];
    }
}
