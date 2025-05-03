<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationImage extends Model
{
    // setup prop
    protected $fillable = [
        'location_id',
        'photo',
        'create_uid',
        'update_uid',
        'is_deleted',
        'deleted_uid',
        'delete_notes',
    ];

    // setup relationship
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }
}
