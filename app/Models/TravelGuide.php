<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class TravelGuide extends Model
{
    protected $fillable = [
        'location_id',
        'best_time_to_visit',
        'local_contacts',
        'currency_and_budget',
        'local_transportation',
        'what_to_pack',
        'what_on_sale',
        'local_etiquette',
        'create_uid',
        'update_uid',
        'is_deleted',
        'deleted_uid',
        'delete_notes',
    ];
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
    protected $casts = [
        'local_contacts' => 'array',
        'currency_and_budget' => 'array',
        'local_transportation' => 'array',
        'what_to_pack' => 'array',
        'local_etiquette' => 'array',
        'what_on_sale' => 'array'
    ];
}
