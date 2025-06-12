<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'latitude',
        'longitude',
        'entrance_fee',
        'transport_fee',
        'nearby_attractions',
        'age_recommendations',
        'recommended_duration_hours',
        'best_time_to_visit',
        'requires_guide',
        'guide_fee',
        'is_active'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'entrance_fee' => 'decimal:2',
        'transport_fee' => 'decimal:2',
        'guide_fee' => 'decimal:2',
        'nearby_attractions' => 'array',
        'age_recommendations' => 'array',
        'requires_guide' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getTotalCostAttribute()
    {
        $cost = $this->entrance_fee + $this->transport_fee;
        if ($this->requires_guide && $this->guide_fee) {
            $cost += $this->guide_fee;
        }
        return $cost;
    }
}
