<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWishlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'item_id',
        'item_type',
        'item_data',
    ];

    protected $casts = [
        'item_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'updated_at',
    ];

    /**
     * Get the user that owns the wishlist item
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the location if item_type is location
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'item_id', 'id')
            ->where('item_type', 'location');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_id', 'product_id')
            ->where('item_type', 'product');
    }

    /**
     * Scope to filter by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by item type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('item_type', $type);
    }

    /**
     * Get formatted wishlist item for API response
     */
    public function getFormattedItemAttribute()
    {
        return [
            'id' => $this->item_id,
            'type' => $this->item_type,
            'addedAt' => $this->created_at->toISOString(),
            'userId' => $this->user_id,
            ...(is_array($this->item_data) ? $this->item_data : [])
        ];
    }

    /**
     * Check if item exists for user
     */
    public static function existsForUser($userId, $itemId, $itemType = 'location')
    {
        return static::where('user_id', $userId)
            ->where('item_id', $itemId)
            ->where('item_type', $itemType)
            ->exists();
    }

    /**
     * Get user's wishlist count
     */
    public static function countForUser($userId)
    {
        return static::where('user_id', $userId)->count();
    }

    /**
     * Get user's wishlist items by type
     */
    public static function getByTypeForUser($userId, $type)
    {
        return static::where('user_id', $userId)
            ->where('item_type', $type)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
