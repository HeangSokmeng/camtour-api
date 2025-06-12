<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget',
        'transportation',
        'departure_location',
        'trip_duration',
        'party_size',
        'age_range',
        'primary_destination',
        'hotel_preference',
        'user_answers',
        'recommendation',
        'total_estimated_cost',
        'recommended_itinerary',
        'session_id'
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'total_estimated_cost' => 'decimal:2',
        'user_answers' => 'array',
        'recommended_itinerary' => 'array'
    ];
}
