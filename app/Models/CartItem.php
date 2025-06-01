<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'variant_id',
        'color_id',
        'size_id',
        'qty',
        'price',
        'create_uid',
        'update_uid',
        'is_deleted',
        'deleted_uid',
        'delete_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Get the cart that owns the item.
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the product associated with the cart item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant associated with the cart item.
     */
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the color associated with the cart item.
     */
    public function color()
    {
        return $this->belongsTo(ProductColor::class);
    }

    /**
     * Get the size associated with the cart item.
     */
    public function size()
    {
        return $this->belongsTo(ProductColor::class);
    }

    /**
     * Get the subtotal for this item.
     */
    public function getSubtotalAttribute()
    {
        return $this->quantity * $this->price;
    }
}
