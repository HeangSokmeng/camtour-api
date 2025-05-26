<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationStarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rater' => [
                'id' => $this->rater->id,
                'first_name' => $this->rater->first_name,
                'last_name' => $this->rater->last_name,
                'gender' => intval($this->rater->gender),
                'image' => asset('storage/users/' . $this->rater->image),
            ],
            'star' => floatval($this->star),
            'comment' => htmlspecialchars_decode($this->comment)
        ];
    }
}
