<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationStar extends Model
{
    // setup prop
    protected $fillable = [
        'rater_id',
        'location_id',
        'star',
        'comment',
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
    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_id', 'id');
    }
}
