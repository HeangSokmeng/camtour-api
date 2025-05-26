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

    protected $casts = [
        'local_contacts' => 'array',
        'currency_and_budget' => 'array',
        'local_transportation' => 'array',
        'what_to_pack' => 'array',
        'local_etiquette' => 'array',
        'what_on_sale' => 'array',
        'is_deleted' => 'boolean'
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    // Scope for non-deleted records
    public function scopeActive($query)
    {
        return $query->where('is_deleted', 0);
    }

    // Override toArray to ensure proper JSON conversion
    public function toArray()
    {
        $array = parent::toArray();

        // Fields that should be JSON decoded
        $jsonFields = [
            'local_contacts',
            'currency_and_budget',
            'local_transportation',
            'what_to_pack',
            'local_etiquette',
            'what_on_sale'
        ];

        foreach ($jsonFields as $field) {
            if (isset($array[$field]) && is_string($array[$field])) {
                $decoded = json_decode($array[$field], true);
                $array[$field] = $decoded !== null ? $decoded : $array[$field];
            }
        }

        return $array;
    }

    // Alternative: Custom accessor methods
    public function getLocalContactsAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : [];
        }
        return $value ?: [];
    }

    public function getCurrencyAndBudgetAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : [];
        }
        return $value ?: [];
    }

    public function getLocalTransportationAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : [];
        }
        return $value ?: [];
    }

    public function getWhatToPackAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : [];
        }
        return $value ?: [];
    }

    public function getLocalEtiquetteAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : [];
        }
        return $value ?: [];
    }

    public function getWhatOnSaleAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : [];
        }
    }}
