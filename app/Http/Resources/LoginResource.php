<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $response = [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => intval($this->gender),
            'image' => asset('storage/avatars/' . $this->image),
            'phone' => $this->phone,
            'email' => $this->email,
            'is_email_verified' => !($this->email_verified_at == null),
            'role' => new RoleResource($this->role),
            'created_at' => Carbon::parse($this->created_at)->format('Y-m-d H:i:s'),
        ];
        if ($this->token) {
            $response['token'] = $this->token;
        }
        return $response;
    }
}
