<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductColor extends Model
{
    // setup prop
    public $timestamps = false;
    protected $table = "product_colors";
    protected $fillable = [
        "product_id",
        'name',
        'code'
    ];
}
