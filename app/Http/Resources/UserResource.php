<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'role_id' => $this->role_id,
            'image' => asset("storage/{$this->users}"),
            'created_at' => Carbon::parse($this->created_at)->format('d/m/Y'),

        ];
    }
}
