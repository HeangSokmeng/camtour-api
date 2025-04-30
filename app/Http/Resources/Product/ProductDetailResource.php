<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductImageResource;
use App\Http\Resources\ProductVariantResource;
use App\Http\Resources\TagResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "name_km" => $this->name_km,
            "status" => $this->status,
            "code" => $this->code,
            "price" => $this->price,
            "description" => $this->description,
            "thumbnail" => asset("storage/{$this->thumbnail}"),
            "category" => new CategoryResource($this->category),
            "product_category" => new ProductCategoryResource($this->pcategory),
            "brand" => new BrandResource($this->brand),
            "tags" => TagResource::collection($this->tags),
            "colors" => $this->colors,
            "sizes" => $this->sizes,
            "images" => ProductImageResource::collection($this->images),
            "variants" => ProductVariantResource::collection($this->variants),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
