<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class CommuneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'local_name' => $this->local_name,
            'province' => [
                'id' => $this->province->id,
                'name' => $this->province->name,
                'local_name' => $this->province->local_name
            ],
            'district' => [
                'id' => $this->district->id,
                'name' => $this->district->name,
                'local_name' => $this->district->local_name
            ],
            'created_at' => Carbon::parse($this->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
