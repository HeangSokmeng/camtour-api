<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commune extends Model
{
    protected $fillable = [
        'province_id',
        'district_id',
        'name',
        'local_name',
        'create_uid',
        'update_uid',
        'is_deleted',
        'deleted_uid',
        'delete_notes',
    ];

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function villages()
    {
        return $this->hasMany(Village::class);
    }

    // Get communes by district ID
    public static function getCommunesByDistrictId($districtId)
    {
        return self::where('district_id', $districtId)->orderByDesc('id')->selectRaw('id,name,local_name,district_id')->get();
    }
        public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
