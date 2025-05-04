<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => asset("storage/{$this->image}"),
            'description' => $this->description,
            'created_at' => Carbon::parse($this->created_at)->format('d/m/Y'),
        ];
    }
}
