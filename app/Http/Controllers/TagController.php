<?php

namespace App\Http\Controllers;

use App\Http\Resources\Tag\IndexResource;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function store(Request $req)
    {
        // validation
        $req->validate([
            'name' => 'required|string|max:250|unique:tags,name',
        ]);

        // store tag & response
        $tag = new Tag($req->only(['name']));
        $tag->save();
        return res_success('Store tag successful.', new TagResource($tag));
    }

    public function index(Request $req)
    {
        // validation
        $req->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'sort_col' => 'nullable|string|in:id,name',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'search' => 'nullable|string|max:50'
        ]);

        // setup default data
        $perPage = $req->filled('per_page') ? $req->input('per_page') : 50;
        $sortCol = $req->filled('sort_col') ? $req->input('sort_col') : 'name';
        $sortDir = $req->filled('sort_dir') ? $req->input('sort_dir') : 'asc';
        $search = $req->filled('search') ? $req->input('search') : '';

        // build query & get tag
        $tags = new Tag();
        if (strlen($search) > 0) {
            $tags = $tags->where('id', $search)->orWhere('name', 'like', "%$search%");
        }
        $tags = $tags->orderBy($sortCol, $sortDir)->paginate($perPage);
        return res_paginate($tags, 'Get all tags successful.', TagResource::collection($tags));
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:tags,id',
            'name' => "nullable|string|max:250|unique:tags,name,$id"
        ]);

        // update tag data
        $tag = Tag::where('id', $id)->first();
        if ($req->filled('name')) {
            $tag->name = $req->input('name');
        }
        $tag->save();
        return res_success('Update tag successful.', new TagResource($tag));
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id'=> $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:tags,id']);

        // delete tag & response
        Tag::where('id', $id)->delete();
        return res_success('Delete tag successful.');
    }
}
