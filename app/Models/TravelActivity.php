<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TravelActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'location_id',
        'image',
        'title',
        'description',
        'duration_hours',
        'difficulty_level',
        'price_per_person',
        'currency',
        'is_active',
        'max_participants',
        'included_items',
        'requirements',
    ];

    protected $casts = [
        'price_per_person' => 'decimal:2',
        'is_active' => 'boolean',
        'included_items' => 'array',
        'requirements' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByImage($query, string $image)
    {
        return $query->where('image', 'like', '%' . $image . '%');
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    public function scopeByPriceRange($query, float $minPrice, float $maxPrice)
    {
        return $query->whereBetween('price_per_person', [$minPrice, $maxPrice]);
    }
}
