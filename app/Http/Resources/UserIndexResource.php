<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class UserIndexResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'is_lock' => $this->is_lock,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name . ' ' . $this->last_name,
            'gender' => $this->gender,
            'gender_text' => $this->getGenderText(),
            'role_id' => $this->role_id,
            'email' => $this->email,
            'phone' => $this->phone,
            'image' => $this->image,
            'image_url' => $this->getImageUrl(),
            'created_at' => $this->created_at,
            'roles' => $this->whenLoaded('roles'),
        ];
    }

    protected function getGenderText()
    {
        switch ($this->gender) {
            case User::GENDER_MALE:
                return 'Male';
            case User::GENDER_FEMALE:
                return 'Female';
            default:
                return 'Unknown';
        }
    }

    protected function getImageUrl()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return asset('storage/no_photo.jpg');
    }
}
