<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationImage extends Model
{
    // setup prop
    protected $fillable = [
        'location_id',
        'photo',
    ];

    // setup relationship
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }
}
