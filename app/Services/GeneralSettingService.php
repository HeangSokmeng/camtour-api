<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Commune;
use App\Models\District;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductSize;
use App\Models\Province;
use App\Models\Tag;
use App\Models\Village;

/**
 * Class GeneralSettingService.
 */
class GeneralSettingService
{
    public static function getOptionsTag($user){
        return Tag::query()->selectRaw('id, name')->orderByDesc('id')->get();
    }
    public static function getOptionsCategory($user){
        return Category::query()->selectRaw('id, name')->orderByDesc('id')->get();
    }
    public static function getOptionsProvince($user){
        return Province::query()->selectRaw('id, name')->orderByDesc('id')->get();
    }
    public static function getOptionsCommune($user){
        return Commune::query()->selectRaw('id, name')->orderByDesc('id')->get();
    }
    public static function getOptionsDistrict($user){
        return District::query()->selectRaw('id, name')->orderByDesc('id')->get();
    }
    public static function getOptionsVillage($user){
        return Village::query()->selectRaw('id, name')->orderByDesc('id')->get();
    }
    public static function getOptionsProduct($user){
        return Product::query()->selectRaw('id, name')->where('is_deleted',0)->orderByDesc('id')->get();
    }
    public static function getOptionsColor($user){
        return ProductColor::query()->selectRaw('id, color')->orderByDesc('id')->get();
    }
    public static function getOptionsSize($user){
        return ProductSize::query()->selectRaw('id, size')->orderByDesc('id')->get();
    }
    public static function getOptionsBrand($user){
        return Brand::query()->selectRaw('id, name')->orderByDesc('id')->get();
    }
}
