<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    // setup prop
    public $timestamps = false;
    protected $table = "product_categories";
    protected $fillable = [
        'name',
        'name_km',
        'create_uid',
        'update_uid',
        'is_deleted',
        'deleted_uid',
        'delete_notes',
    ];
}
