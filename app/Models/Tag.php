<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    // setup prop
    public $timestamps = false;
    protected $fillable = ['name'];

    // setup relationship
    public function locations()
    {
        return $this->belongsToMany(Location::class, 'location_tag', 'tag_id', 'location_id');
    }
}
