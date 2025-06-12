<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'star_rating',
        'price_per_night',
        'latitude',
        'longitude',
        'amenities',
        'room_types',
        'contact_phone',
        'contact_email',
        'address',
        'is_active'
    ];

    protected $casts = [
        'price_per_night' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'amenities' => 'array',
        'room_types' => 'array',
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStar($query, $stars)
    {
        return $query->where('star_rating', $stars);
    }
}

