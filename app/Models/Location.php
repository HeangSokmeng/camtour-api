<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    // setup const
    public const DEFAULT_THUMBNAIL = "locations/thumbnails/no_thumbnail.jpg";

    // setup prop
    protected $fillable = [
        'name',
        'name_local',
        'thumbnail',
        'url_location',
        'short_description',
        'description',
        'lat',
        'lot',
        'category_id',
        'province_id',
        'district_id',
        'commune_id',
        'village_id',
    ];

    // setup relationship
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'location_tag', 'location_id', 'tag_id');
    }
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
    public function photos()
    {
        return $this->hasMany(LocationImage::class, 'location_id', 'id');
    }
    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'id');
    }
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'id');
    }
    public function commune()
    {
        return $this->belongsTo(Commune::class, 'commune_id', 'id');
    }
    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id', 'id');
    }
    public function stars()
    {
        return $this->hasMany(LocationStar::class, 'location_id', 'id');
    }
}
