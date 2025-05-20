<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Village extends Model
{
    protected $fillable = [
        'province_id',
        'district_id',
        'commune_id',
        'name',
        'local_name',
        'create_uid',
        'update_uid',
        'is_deleted',
        'deleted_uid',
        'delete_notes',
    ];

    public function commune()
    {
        return $this->belongsTo(Commune::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    // Get villages by commune ID
    public static function getVillagesByCommuneId($communeId)
    {
        return self::where('commune_id', $communeId)->orderByDesc('id')->selectRaw('id,name,local_name,commune_id')->get();
    }
}
