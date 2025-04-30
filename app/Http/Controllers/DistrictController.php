<?php

namespace App\Http\Controllers;

use App\Http\Resources\DistrictResource;
use App\Models\District;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    public function index(Request $req)
    {
        // validation
        $req->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'sort_col' => 'nullable|string|in:id,name,local_name,province_id',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'search' => 'nullable|string|max:50',
            'province' => 'nullable|integer|min:1|exits:provinces,id'
        ]);

        // setup default value
        $perPage = $req->filled('per_page') ? $req->input('per_page') : 50;
        $sortCol = $req->filled('sort_col') ? $req->input('sort_col') : 'name';
        $sortDir = $req->filled('sort_dir') ? $req->input('sort_dir') : 'asc';
        $search = $req->filled('search') ? $req->input('search') : '';
        $provinceId = $req->filled('province') ? $req->input('province') : 0;

        // build query and get
        $districts = new District();
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
        // validation
        $req->validate([
            'province_id' => 'required|integer|min:1|exists:provinces,id',
            'name' => 'required|string|max:250',
            'local_name' => 'required|string|max:250',
        ]);

        // store new district
        $district = new District($req->only(['name', 'local_name', 'province_id']));
        $district->save();
        return res_success('Create district successful.', new DistrictResource($district));
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:districts,id',
            'province_id' => 'nullable|integer|min:1|exists:provinces,id',
            'name' => 'nullable|string|max:250',
            'local_name' => 'nullable|string|max:250',
        ]);

        // update district data
        $district = District::where('id', $id)->first();
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
        // validation
        $req->merge(['id'=> $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:districts,id']);

        // delete district
        $district = District::where('id', $id)->first();
        $district->delete();
        return res_success('Delete district successful.');
    }

    public function find(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate(['id' => 'required|integer|min:1|exits:districts,id']);

        // get district and response
        $district = District::where('id', $id)->with('province')->first();
        return res_success('Get one district', new DistrictResource($district));
    }
}
