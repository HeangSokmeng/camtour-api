<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSize extends Model
{
    // setup prop
    public $timestamps = false;
    protected $table = "product_sizes";
    protected $fillable = [
        "product_id",
        'size',
        'create_uid',
        'update_uid',
        'is_deleted',
        'deleted_uid',
        'delete_notes',
    ];

    // setup relationship
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
