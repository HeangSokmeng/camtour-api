<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'location_id',
        'comment',
        'photos',
        'status',
        'create_uid',
        'update_uid',
        'is_deleted',
        'deleted_uid',
        'delete_notes',
    ];

    protected $casts = [
        'photos' => 'array',
        'status' => 'boolean'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'create_uid');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'update_uid');
    }
}
