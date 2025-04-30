<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    // setup prop
    public $timestamps = false;
    protected $table = "product_categories";
    protected $fillable = ['name'];
}
