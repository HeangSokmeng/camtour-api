<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    // global const
    public const CUSTOMER = 4;
    public const STAFF = 3;
    public const ADMIN = 2;
    public const SYSTEM_ADMIN = 1;

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

    // setup relationship// app/Models/Role.php
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

}
