<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProvinceResource;
use App\Models\Province;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    public function index(Request $req)
    {
        // validation
        $req->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'sort_col' => 'nullable|string|in:id,name,local_name',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'search' => 'nullable|string'
        ]);

        // setup default data
        $perPage = $req->filled('per_page') ? $req->input('per_page') : 50;
        $sortCol = $req->filled('sort_col') ? $req->input('sort_col') : 'name';
        $sortDir = $req->filled('sort_dir') ? $req->input('sort_dir') : 'asc';
        $search = $req->filled('search') ? $req->input('search') : '';

        // build query & get data
        $provinces = new Province();
        if (strlen($search) > 0) {
            $provinces = $provinces->where('id', $search)
                ->orWhere('name', 'like', "%$search%")
                ->orWhere('local_name', 'like', "%$search%");
        }
        $provinces = $provinces->orderBy($sortCol, $sortDir)->paginate($perPage);
        return res_paginate($provinces, 'Get all provinces successful.', ProvinceResource::collection($provinces));
    }

    public function store(Request $req)
    {
        // validation
        $req->validate([
            'name' => 'required|string|max:250|unique:provinces,name',
            'local_name' => 'required|string|max:250|unique:provinces,local_name',
        ]);

        // store new province
        $province = new Province($req->only(['name', 'local_name']));
        $province->save();
        return res_success('Create province successful.', new ProvinceResource($province));
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:provinces,id',
            'name' => "nullable|string|max:250|unique:provinces,name,$id",
            'local_name' => "nullable|string|max:250|unique:provinces,local_name,$id"
        ]);

        // update province
        $province = Province::where('id', $id)->first();
        if ($req->filled('name')) {
            $province->name = $req->input('name');
        }
        if ($req->filled('local_name')) {
            $province->local_name = $req->input('local_name');
        }
        $province->save();
        return res_success('Update province successful.', new ProvinceResource($province));
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id'=> $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:provinces,id',
        ]);

        // delete province
        $province = Province::where('id', $id)->first();
        $province->delete();
        return res_success('Delete province successful.');
    }
}
