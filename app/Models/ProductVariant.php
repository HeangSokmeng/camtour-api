<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    // setup prop
    public $timestamps = false;
    protected $table = "product_variants";
    protected $fillable = [
        'product_id',
        'product_color_id',
        'product_size_id',
        'qty',
        'price',
        'create_uid',
        'update_uid',
        'is_deleted',
        'deleted_uid',
        'delete_notes',
    ];

    // setup relationship
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
    public function color()
    {
        return $this->belongsTo(ProductColor::class, 'product_color_id', 'id');
    }
    public function size()
    {
        return $this->belongsTo(ProductSize::class, 'product_size_id', 'id');
    }
}
