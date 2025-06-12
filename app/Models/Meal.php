<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'name',
        'description',
        'price_per_person',
        'cuisine_type',
        'meal_type',
        'dietary_options',
        'preparation_time_minutes',
        'location_type',
        'is_popular',
        'is_active'
    ];

    protected $casts = [
        'price_per_person' => 'decimal:2',
        'dietary_options' => 'array',
        'is_popular' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeByCuisine($query, $cuisine)
    {
        return $query->where('cuisine_type', $cuisine);
    }
}
