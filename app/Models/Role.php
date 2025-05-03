<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    // global const
    public const SYSTEM_ADMIN = 2;
    public const REGULAR_USER = 1;

    // setup prop
    public $timestamps  = false;
    protected $fillable = [
        'name',
        'create_uid',
        'update_uid',
        'is_deleted',
        'deleted_uid',
        'delete_notes'
    ];

    // setup relationship
    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'id');
    }
}
