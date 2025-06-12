<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportationCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_location',
        'to_location',
        'transportation_type',
        'cost',
        'duration_minutes',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRoute($query, $from, $to, $type)
    {
        return $query->where('from_location', $from)
            ->where('to_location', $to)
            ->where('transportation_type', $type);
    }
}
