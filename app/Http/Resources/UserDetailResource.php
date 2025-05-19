<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name . ' ' . $this->last_name,
            'gender' => $this->gender,
            'gender_text' => $this->getGenderText(),
            'role_id' => $this->role_id,
            'image' => $this->getImageUrl(),
            'image_url' => $this->getImageUrl(),
            'phone' => $this->phone,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'roles' => $this->whenLoaded('roles'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
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
