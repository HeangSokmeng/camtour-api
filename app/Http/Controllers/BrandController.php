<?php

namespace App\Http\Controllers;

use App\Http\Resources\BrandResource;
use Illuminate\Http\Request;
use App\Services\UserService;
use ApiResponse;
use App\Models\Brand;
use App\Models\Brands;
use App\Models\ProductModel;

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
            'name' => 'required|string|max:250|unique:brands,name',
            'name_km' => 'required|string|max:250|unique:brands,name_km'
        ]);

        // store new brand
        $brand = new Brand($req->only(['name', 'name_km']));
        $brand->save();
        return res_success('Store new brand successful.');
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:brands,id',
            'name' => "required|string|max:250|unique:brands,name,$id",
            "name_km" => "required|string|max:250|unique:brands,name_km,$id"
        ]);

        // update brand
        Brand::where('id', $id)->update($req->only(['name', 'name_km']));
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
        $brands = $brands->orderByDesc('id')->get();
        return res_success('Get all brands successful.', BrandResource::collection($brands));
    }


    public function find(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:brands,id'
        ]);

        // get one branch
        $brand = Brand::where('id', $id)->first();
        return res_success('Get one brand successful', new BrandResource($brand));
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|exists:brands,id'
        ]);

        // delete brand
        $brand = Brand::where('id', $id)->delete();
        return res_success('Delete brand successful.');
    }
}
