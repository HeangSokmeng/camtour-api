<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStar extends Model
{
    // setup prop
    protected $fillable = [
        'rater_id',
        'product_id',
        'star',
        'status',
        'comment',
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
    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_id', 'id');
    }
}
