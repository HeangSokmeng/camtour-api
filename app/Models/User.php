<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    // global const
    public const GENDER_MALE = 1;
    public const GENDER_FEMALE = 2;
    public const GENDER_UNKNOWN = null;
    public const DEFAULT_IMAGE = 'no_photo.jpg';

    // setup prop
    protected $fillable = [
        'first_name',
        'last_name',
        'gender',
        'role_id',
        'image',
        'phone',
        'email',
        'password',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // setup option
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // setup relationship
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }
    public function location_stars()
    {
        return $this->hasMany(LocationStar::class, 'rater_id', 'id');
    }
}
