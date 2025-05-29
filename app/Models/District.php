<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $fillable = [
        'province_id',
        'name',
        'local_name',
        'create_uid',
        'update_uid',
        'is_deleted',
        'deleted_uid',
        'delete_notes',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function communes()
    {
        return $this->hasMany(Commune::class);
    }

    // Get districts by province ID
    public static function getDistrictsByProvinceId($provinceId)
    {
        return self::where('province_id', $provinceId)->orderByDesc('id')->selectRaw('id,name,local_name,province_id')->get();
    }
        public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
