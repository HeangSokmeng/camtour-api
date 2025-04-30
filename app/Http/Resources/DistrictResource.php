<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistrictResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'local_name' => $this->local_name,
            'province' => [
                'id' => $this->province->id,
                'name'=> $this->province->name,
                'local_name' => $this->province->local_name
            ],
        ];
    }
}
