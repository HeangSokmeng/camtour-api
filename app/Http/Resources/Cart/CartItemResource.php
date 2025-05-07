<?php

namespace App\Http\Resources\Cart;

use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product' => $this->whenLoaded('product', function() {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'name_km' => $this->product->name_km,
                    'code' => $this->product->code,
                    'thumbnail' => $this->product->thumbnail,
                    'price' => $this->product->price,
                ];
            }),
            'variant' => $this->whenLoaded('variant', function() {
                return $this->variant ? [
                    'id' => $this->variant->id,
                    'name' => $this->variant->name ?? '',
                    'price' => $this->variant->price ?? 0,
                ] : null;
            }),
            'color' => $this->whenLoaded('color', function() {
                return $this->color ? [
                    'id' => $this->color->id,
                    'name' => $this->color->name ?? '',
                    'color_code' => $this->color->color_code ?? '',
                ] : null;
            }),
            'size' => $this->whenLoaded('size', function() {
                return $this->size ? [
                    'id' => $this->size->id,
                    'name' => $this->size->name ?? '',
                ] : null;
            }),
            'quantity' => $this->quantity,
            'price' => $this->price,
            'subtotal' => $this->subtotal,
        ];
    }
}
