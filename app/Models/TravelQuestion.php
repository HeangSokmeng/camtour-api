<?php

namespace App\Models;

use App\Helpers\JsonExporter;
use Illuminate\Database\Eloquent\Model;

class TravelQuestion extends Model
{
    protected $fillable = [
        'location',
        'category',
        'question',
        'answer',
        'media',
        'links',
        'create_uid',
        'update_uid',
        'deleted_uid',
        'deleted_datetime'
    ];

    protected $casts = [
        'media' => 'array',
        'links' => 'array',
        'deleted_datetime' => 'datetime',
        'is_deleted' => 'boolean'
    ];

    protected $hidden = [
        'create_uid',
        'update_uid',
        'deleted_uid',
        'deleted_datetime',
        'is_deleted'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_deleted', 0);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', 'like', '%' . $location . '%');
    }

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'create_uid');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'update_uid');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_uid');
    }
    protected static function booted()
    {
        static::created(function () {
            JsonExporter::exportToJson();
        });

        static::updated(function () {
            JsonExporter::exportToJson();
        });

        static::deleted(function () {
            JsonExporter::exportToJson();
        });
    }
}
