<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    // setup prop
    public $timestamps = false;
    protected $table = "product_images";
    protected $fillable = [
        'product_id',
        'image'
    ];

    // setup relationship
    public function product()
    {
        return $this->belongsTo(Product::class, "product_id", "id");
    }
}
