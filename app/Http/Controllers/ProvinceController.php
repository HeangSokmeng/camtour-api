<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProvinceResource;
use App\Models\Province;
use Illuminate\Http\Request;
use App\Services\UserService;

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
        $provinces = $provinces->where('is_deleted', 0);
        if (strlen($search) > 0) {
            $provinces = $provinces->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('name', 'like', "%$search%")
                    ->orWhere('local_name', 'like', "%$search%");
            });
        }
        $provinces = $provinces->orderBy($sortCol, $sortDir)->paginate($perPage);
        return res_paginate($provinces, 'Get all provinces successful.', ProvinceResource::collection($provinces));
    }

    public function store(Request $req)
    {
        // validation
        $req->validate([
            'name' => 'required|string|max:250|unique:provinces,name,NULL,id,is_deleted,0',
            'local_name' => 'required|string|max:250|unique:provinces,local_name,NULL,id,is_deleted,0',
        ]);
        // store new province
        $province = new Province($req->only(['name', 'local_name']));
        $user = UserService::getAuthUser($req);
        $province->create_uid = $user->id;
        $province->update_uid = $user->id;
        $province->save();
        return res_success('Create province successful.', new ProvinceResource($province));
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:provinces,id,is_deleted,0',
            'name' => "nullable|string|max:250|unique:provinces,name,$id,id,is_deleted,0",
            'local_name' => "nullable|string|max:250|unique:provinces,local_name,$id,id,is_deleted,0"
        ]);

        // update province
        $province = Province::where('id', $id)->where('is_deleted', 0)->first();
        if (!$province) return res_fail('Province not found.', [], 1, 404);
        $user = UserService::getAuthUser($req);
        $province->update_uid = $user->id;
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
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:provinces,id,is_deleted,0',
        ]);
        // find province
        $province = Province::where('id', $id)->where('is_deleted', 0)->first();
        if (!$province) return res_fail('Province not found.', [], 1, 404);
        // soft delete
        $user = UserService::getAuthUser($req);
        $province->update([
            'is_deleted' => 1,
            'deleted_uid' => $user->id,
            'deleted_datetime' => now()
        ]);
        return res_success('Delete province successful.');
    }

    public function find(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:provinces,id,is_deleted,0']);
        // get one province
        $province = Province::where('id', $id)->where('is_deleted', 0)->first();
        if (!$province) return res_fail('Province not found.', [], 1, 404);
        return res_success('Get one province successful.', new ProvinceResource($province));
    }
}
