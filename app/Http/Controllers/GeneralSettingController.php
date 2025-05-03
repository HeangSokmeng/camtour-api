<?php

namespace App\Http\Controllers;

use ApiResponse;
use App\Services\GeneralSettingService;
use App\Services\UserService;
use Illuminate\Http\Request;

class GeneralSettingController extends Controller
{
    protected $gs;
    public function __construct(GeneralSettingService $gs)
    {
        $this->gs = $gs;
    }
    public function getFormLocation(Request $req)
    {
        $user = UserService::getAuthUser($req);
        $obj = (object)[
            'tags' => $this->gs::getOptionsTag($user),
            'category' => $this->gs::getOptionsCategory($user),
            'province' => $this->gs::getOptionsProvince($user),
            'district' => $this->gs::getOptionsDistrict($user),
            'commune' => $this->gs::getOptionsCommune($user),
            'village' => $this->gs::getOptionsVillage($user),
        ];
        return ApiResponse::JsonResult($obj);
    }

    public function getFormVariant(Request $req)
    {
        $user = UserService::getAuthUser($req);
        $obj = (object)[
            'product' => $this->gs::getOptionsProduct($user),
            'color' => $this->gs::getOptionsColor($user),
            'size' => $this->gs::getOptionsSize($user),
        ];
        return ApiResponse::JsonResult($obj);
    }

    public function getFormProduct(Request $req)
    {
        $user = UserService::getAuthUser($req);
        $obj = (object)[
            'category' => $this->gs::getOptionsCategory($user),
            'brand' => $this->gs::getOptionsBrand($user),
            'color' => $this->gs::getOptionsColor($user),
            'size' => $this->gs::getOptionsSize($user),
        ];
        return ApiResponse::JsonResult($obj);
    }
}
