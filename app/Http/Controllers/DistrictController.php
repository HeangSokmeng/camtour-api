<?php

namespace App\Http\Controllers;

use App\Http\Resources\DistrictResource;
use App\Models\District;
use Illuminate\Http\Request;
use App\Services\UserService;

class DistrictController extends Controller
{
    public function index(Request $req)
    {
        $req->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'sort_col' => 'nullable|string|in:id,name,local_name,province_id',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'search' => 'nullable|string|max:50',
            'province' => 'nullable|integer|min:1|exists:provinces,id'
        ]);
        $perPage = $req->filled('per_page') ? $req->input('per_page') : 50;
        $sortCol = $req->filled('sort_col') ? $req->input('sort_col') : 'name';
        $sortDir = $req->filled('sort_dir') ? $req->input('sort_dir') : 'asc';
        $search = $req->filled('search') ? $req->input('search') : '';
        $provinceId = $req->filled('province') ? $req->input('province') : 0;
        $districts = new District();
        $districts = $districts->where('is_deleted', 0);
        if (strlen($search) > 0) {
            $districts = $districts->where(function ($query) use ($search) {
                $query->where('id', $search)
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('local_name', 'like', '%' . $search . '%');
            });
        }
        if ($provinceId > 0) {
            $districts = $districts->where('province_id', $provinceId);
        }
        $districts = $districts->with('province')
            ->orderBy($sortCol, $sortDir)
            ->paginate($perPage);
        return res_paginate($districts, 'Get all districts successful.', DistrictResource::collection($districts));
    }

    public function store(Request $req)
    {
        $req->validate([
            'province_id' => 'required|integer|min:1|exists:provinces,id',
            'name' => 'required|string|max:250',
            'local_name' => 'required|string|max:250',
        ]);
        $district = new District($req->only(['name', 'local_name', 'province_id']));
        $user = UserService::getAuthUser($req);
        $district->create_uid = $user->id;
        $district->update_uid = $user->id;
        $district->save();
        return res_success('Create district successful.', new DistrictResource($district));
    }

    public function update(Request $req, $id)
    {
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:districts,id,is_deleted,0',
            'province_id' => 'nullable|integer|min:1|exists:provinces,id',
            'name' => 'nullable|string|max:250',
            'local_name' => 'nullable|string|max:250',
        ]);
        $district = District::where('id', $id)->where('is_deleted', 0)->first();
        if (!$district) return res_fail('District not found.', [], 1, 404);
        $user = UserService::getAuthUser($req);
        $district->update_uid = $user->id;
        if ($req->filled('name')) {
            $district->name = $req->input('name');
        }
        if ($req->filled('local_name')) {
            $district->local_name = $req->input('local_name');
        }
        if ($req->filled('province_id')) {
            $district->province_id = $req->input('province_id');
        }
        $district->save();
        return res_success('Update district successful.', new DistrictResource($district));
    }

    public function destroy(Request $req, $id)
    {
        $req->merge(['id'=> $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:districts,id,is_deleted,0']);
        $district = District::where('id', $id)->where('is_deleted', 0)->first();
        if (!$district) return res_fail('District not found.', [], 1, 404);
        $user = UserService::getAuthUser($req);
        $district->update([
            'is_deleted' => 1,
            'deleted_uid' => $user->id,
            'deleted_datetime' => now()
        ]);
        return res_success('Delete district successful.');
    }

    public function find(Request $req, $id)
    {
        $req->merge(['id' => $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:districts,id,is_deleted,0']);
        $district = District::where('id', $id)->where('is_deleted', 0)->with('province')->first();
        if (!$district) return res_fail('District not found.', [], 1, 404);
        return res_success('Get one district successful.', new DistrictResource($district));
    }
}
