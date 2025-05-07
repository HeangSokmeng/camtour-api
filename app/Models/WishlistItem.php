<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WishlistItem extends Model
{
    protected $fillable = ['user_id', 'location_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
