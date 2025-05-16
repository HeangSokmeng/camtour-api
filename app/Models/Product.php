<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    // setup constant
    public const DEFAULT_THUMBNAIL = 'no_thumbnail.jpg';

    // setup prop
    protected $table = 'products';
    protected $fillable = [
        'brand_id',
        'category_id',
        'product_category_id',
        'name',
        'name_km',
        'code',
        'thumbnail',
        'description',
        'status',
        'total_views',
        'price',
        'create_uid',
        'update_uid',
        'is_deleted',
        'deleted_uid',
        'delete_notes',
    ];

    // setup relationship
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function pcategory()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id', 'id');
    }
    public function colors()
    {
        return $this->hasMany(ProductColor::class, 'product_id', 'id');
    }
    public function sizes()
    {
        return $this->hasMany(ProductSize::class, 'product_id', 'id');
    }
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tags', 'product_id', 'tag_id');
    }
    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id', 'id');
    }
}
