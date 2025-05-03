<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    // setup const
    public const DEFAULT_IMAGE = 'categories/no_photo.jpg';

    // setup prop
    public $timestamps = false;
    protected $fillable = [
        'name',
        'description',
        'image',
        'create_uid',
        'update_uid',
        'is_deleted',
        'deleted_uid',
        'delete_notes',
    ];

    // setup relationship
    public function locations()
    {
        return $this->hasMany(Location::class, 'category_id', 'id');
    }
}
