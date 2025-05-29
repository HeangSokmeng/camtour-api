<?php

namespace App\Http\Controllers;

use App\Http\Resources\BrandResource;
use Illuminate\Http\Request;
use App\Services\UserService;
use ApiResponse;
use App\Models\Brand;

class BrandController
{
    public function BrandValidation(Request $req)
    {
        return validator($req->all(), [
            'name' => 'required|string|max:50',
            'name_km' => 'nullable|string|max:50'
        ]);
    }

    public function store(Request $req)
    {
        // validation
        $req->validate([
            'name' => 'required|string|max:250|unique:brands,name,NULL,id,is_deleted,0',
            'name_km' => 'required|string|max:250|unique:brands,name_km,NULL,id,is_deleted,0'
        ]);
        // store new brand
        $brand = new Brand($req->only(['name', 'name_km']));
        $user = UserService::getAuthUser($req);
        $brand->create_uid = $user->id;
        $brand->update_uid = $user->id;
        $brand->save();
        return res_success('Store new brand successful.');
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:brands,id',
            'name' => "required|string|max:250|unique:brands,name,$id,id,is_deleted,0",
            "name_km" => "required|string|max:250|unique:brands,name_km,$id,id,is_deleted,0"
        ]);

        $brand = Brand::where('id', $id)->where('is_deleted', 0)->first();
        if (!$brand)  return res_fail('Brand not found.', [], 1, 404);
        $user = UserService::getAuthUser($req);
        $brand->update_uid = $user->id;
        $brand->update($req->only(['name', 'name_km']));
        return res_success('Update brand successful.');
    }

    public function index(Request $req)
    {
        // validation
        $req->validate([
            'search' => 'nullable|string|max:50'
        ]);
        // add search option
        $brands = new Brand();
        if ($req->filled('search')) {
            $s = $req->input('search');
            $brands = $brands->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                    ->where('name_km', 'like', "%$s%");
            });
        }
        // get brands & response
        $brands = $brands->where('is_deleted', 0)->orderByDesc('id')->get();
        return res_success('Get all brands successful.', BrandResource::collection($brands));
    }


    public function find(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:brands,id,is_deleted,0'
        ]);
        // get one branch
        $brand = Brand::where('id', $id)->where('is_deleted', 0)->first();
        return res_success('Get one brand successful', new BrandResource($brand));
    }

    public function destroy(Request $req, $id)
    {
        $user = UserService::getAuthUser($req);
        $id = $req->id;
        $Brand = Brand::where('is_deleted', 0)->find($id);
        if (!$Brand) return ApiResponse::NotFound('Brand not found');
        $Brand->update([
            'is_deleted' => 1,
            'deleted_uid' => $user->id,
            'deleted_datetime' => now()
        ]);
        return ApiResponse::JsonResult(null, 'Deleted');
    }
}
