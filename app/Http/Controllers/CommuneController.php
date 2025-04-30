<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommuneResource;
use App\Models\Commune;
use Illuminate\Http\Request;

class CommuneController extends Controller
{
    public function index(Request $req)
    {
        // validation
        $req->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'sort_col' => 'nullable|string|in:id,name,local_name,province_id,district_id',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'search' => 'nullable|string|max:50',
            'province' => 'nullable|integer|min:1|exists:provinces,id',
            'district' => 'nullable|integer|min:1|exists:districts,id',
        ]);

        // setup default data
        $perPage = $req->filled('per_page') ? $req->input('per_page') : 50;
        $sortCol = $req->filled('sort_col') ? $req->input('sort_col') : 'name';
        $sortDir = $req->filled('sort_dir') ? $req->input('sort_dir') : 'asc';
        $search = $req->filled('search') ? $req->input('search') : '';
        $provinceId = $req->filled('province') ? $req->input('province') : 0;
        $districtId = $req->filled('district') ? $req->input('district') : 0;

        // build query & get data
        $communes = new Commune();
        if (strlen($search) > 0) {
            $communes = $communes->where(function ($query) use ($search) {
                $query->where('id', $search)
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('local_name', 'like', '%' . $search . '%');
            });
        }
        if ($provinceId > 0) {
            $communes = $communes->where('province_id', $provinceId);
        }
        if ($districtId > 0) {
            $communes = $communes->where('district_id', $districtId);
        }
        $communes = $communes->with(['province', 'district'])
            ->orderBy($sortCol, $sortDir)
            ->paginate($perPage);
        return res_paginate($communes, 'Get all communes successful.', CommuneResource::collection($communes));
    }

    public function store(Request $req)
    {
        // validation
        $req->validate([
            'name' => 'required|string|max:250',
            'local_name' => 'required|string|max:250',
            'province_id' => 'required|integer|min:1|exists:provinces,id',
            'district_id' => 'required|integer|min:1|exists:districts,id'
        ]);

        // store commune
        $commune = new Commune($req->only(['name', 'local_name', 'province_id', 'district_id']));
        $commune->save();
        return res_success('Create commune successful.', new CommuneResource($commune));
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->validate([
            'id' => 'required|integer|min:1|exists:communes,id',
            'name' => 'nullable|string|max:250',
            'local_name' => 'nullable|string|max:250',
            'province_id' => 'nullable|integer|min:1|exists:provinces,id',
            'district_id' => 'nullable|integer|min:1|exists:districts,id',
        ]);

        // update data & response
        $commune = Commune::where('id', $id)->first();
        if ($req->filled('name')) {
            $commune->name = $req->input('name');
        }
        if ($req->filled('local_name')) {
            $commune->local_name = $req->input('local_name');
        }
        if ($req->filled('province_id')) {
            $commune->province_id = $req->input('province_id');
        }
        if ($req->filled('district_id')) {
            $commune->district_id = $req->input('district_id');
        }
        $commune->save();
        return res_success('Update commune successful.', new CommuneResource($commune));
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:communes,id']);

        // delete commune
        $commune = Commune::where('id', $id)->first();
        $commune->delete();
        return res_success('Delete commune successful.');
    }

    public function find(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:communes,id']);

        // get commune
        $commune = Commune::where('id', $id)->with('province', 'district')->first();
        return res_success('Get one commune success.', new CommuneResource($commune));
    }
}
