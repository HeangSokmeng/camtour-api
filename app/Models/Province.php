<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use function Laravel\Prompts\select;

class Province extends Model
{
    protected $fillable = [
        'name',
        'local_name',
        'create_uid',
        'update_uid',
        'is_deleted',
        'deleted_uid',
        'delete_notes',
    ];
     public function districts()
    {
        return $this->hasMany(District::class);
    }

    // Get all provinces
    public static function getAllProvinces()
    {
        return self::orderByDesc('id')->selectRaw('id,name,local_name')->get();
    }
}
