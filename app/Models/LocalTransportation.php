<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalTransportation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'description',
        'price_per_hour',
        'price_per_day',
        'price_per_trip',
        'estimated_daily_cost',
        'capacity_people',
        'suitable_for',
        'advantages',
        'disadvantages',
        'booking_method',
        'driver_included',
        'is_active'
    ];

    protected $casts = [
        'price_per_hour' => 'decimal:2',
        'price_per_day' => 'decimal:2',
        'price_per_trip' => 'decimal:2',
        'suitable_for' => 'array',
        'advantages' => 'array',
        'disadvantages' => 'array',
        'driver_included' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getEstimatedDailyCostAttribute()
    {
        if ($this->price_per_day) {
            return $this->price_per_day;
        } elseif ($this->price_per_hour) {
            return $this->price_per_hour * 8; // 8 hours per day
        } elseif ($this->price_per_trip) {
            return $this->price_per_trip * 4; // 4 trips per day
        }
        return 0;
    }
}
