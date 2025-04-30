<?php

namespace App\Http\Controllers;

use App\Http\Resources\VillageResource;
use App\Models\Village;
use Illuminate\Http\Request;

class VillageController extends Controller
{
    public function index(Request $req)
    {
        // validation
        $req->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'sort_col' => 'nullable|string|in:id,name,local_name,province_id,district_id,commune_id',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'search' => 'nullable|string|max:50',
            'province' => 'nullable|integer|min:1|exists:provinces,id',
            'district' => 'nullable|integer|min:1|exists:districts,id',
            'commune' => 'nullable|integer|min:1|exists:communes,id'
        ]);

        // setup default data
        $perPage = $req->filled('per_page') ? $req->input('per_page') : 50;
        $sortCol = $req->filled('sort_col') ? $req->input('sort_col') : 'name';
        $sortDir = $req->filled('sort_dir') ? $req->input('sort_dir') : 'asc';
        $search = $req->filled('search') ? $req->input('search') : '';
        $provinceId = $req->filled('province') ? $req->input('province') : 0;
        $districtId = $req->filled('district') ? $req->input('district') : 0;
        $communeId = $req->filled('commune') ? $req->input('commune') : 0;

        // build query & get data
        $villages = new Village();
        if (strlen($search) > 0) {
            $villages = $villages->where(function ($query) use ($search) {
                $query->where('id', $search)
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('local_name', 'like', '%' . $search . '%');
            });
        }
        if ($provinceId > 0) {
            $villages = $villages->where('province_id', $provinceId);
        }
        if ($districtId > 0) {
            $villages = $villages->where('district_id', $districtId);
        }
        if ($communeId > 0) {
            $villages = $villages->where('commune_id', $communeId);
        }
        $villages = $villages->with(['province', 'district', 'commune'])
            ->orderBy($sortCol, $sortDir)
            ->paginate($perPage);
        return res_paginate($villages, 'Get all villages successful.', VillageResource::collection($villages));
    }

    public function store(Request $req)
    {
        // validation
        $req->validate([
            'name' => 'required|string|max:250',
            'local_name' => 'required|string|max:250',
            'province_id' => 'required|integer|min:1|exists:provinces,id',
            'district_id' => 'required|integer|min:1|exists:districts,id',
            'commune_id' => 'required|integer|min:1|exists:communes,id',
        ]);

        // store new village
        $village = new Village($req->only(['name', 'local_name', 'province_id', 'district_id', 'commune_id']));
        $village->save();
        return res_success('Create village successful.', new VillageResource($village));
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:villages,id',
            'name' => 'nullable|string|max:250',
            'local_name' => 'nullable|string|max:250',
            'province_id' => 'nullable|integer|min:1|exists:provinces,id',
            'district_id' => 'nullable|integer|min:1|exists:districts,id',
            'commune_id' => 'nullable|integer|min:1|exists:communes,id',
        ]);

        // update data
        $village = Village::where('id', $id)->first();
        if ($req->filled('name')) {
            $village->name = $req->input('name');
        }
        if ($req->filled('local_name')) {
            $village->local_name = $req->input('local_name');
        }
        if ($req->filled('province_id')) {
            $village->province_id = $req->input('province_id');
        }
        if ($req->filled('district_id')) {
            $village->district_id = $req->input('district_id');
        }
        if ($req->filled('commune_id')) {
            $village->commune_id = $req->input('commune_id');
        }
        $village->save();
        return res_success('Update village successful.', new VillageResource($village));
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:villages,id']);

        // delete village
        $village = Village::where('id', $id)->first();
        $village->delete();
        return res_success('Delete village successful.');
    }
    public function find(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:villages,id']);

        // get one village
        $village = Village::where('id', $id)->with(['province', 'district', 'commune'])->first();
        return res_success('Get one village success.', new VillageResource($village));
    }
}
