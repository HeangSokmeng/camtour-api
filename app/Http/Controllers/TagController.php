<?php

namespace App\Http\Controllers;

use App\Http\Resources\Tag\IndexResource;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Request;
use App\Services\UserService;

class TagController extends Controller
{
    public function store(Request $req)
    {
        // validation
        $req->validate([
            'name' => 'required|string|max:250|unique:tags,name,NULL,id,is_deleted,0',
        ]);
        // store tag & response
        $tag = new Tag($req->only(['name']));
        $user = UserService::getAuthUser($req);
        $tag->create_uid = $user->id;
        $tag->update_uid = $user->id;
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
        $tags = $tags->where('is_deleted', 0);
        if (strlen($search) > 0) {
            $tags = $tags->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('name', 'like', "%$search%");
            });
        }
        $tags = $tags->orderBy($sortCol, $sortDir)->paginate($perPage);
        return res_paginate($tags, 'Get all tags successful.', TagResource::collection($tags));
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:tags,id,is_deleted,0',
            'name' => "nullable|string|max:250|unique:tags,name,$id,id,is_deleted,0"
        ]);
        // update tag data
        $tag = Tag::where('id', $id)->where('is_deleted', 0)->first();
        if (!$tag) return res_fail('Tag not found.', [], 1, 404);
        $user = UserService::getAuthUser($req);
        $tag->update_uid = $user->id;
        if ($req->filled('name')) {
            $tag->name = $req->input('name');
        }
        $tag->save();
        return res_success('Update tag successful.', new TagResource($tag));
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:tags,id,is_deleted,0']);
        // find tag
        $tag = Tag::where('id', $id)->where('is_deleted', 0)->first();
        if (!$tag) return res_fail('Tag not found.', [], 1, 404);
        // soft delete
        $user = UserService::getAuthUser($req);
        $tag->update([
            'is_deleted' => 1,
            'deleted_uid' => $user->id,
            'deleted_datetime' => now()
        ]);
        return res_success('Delete tag successful.');
    }

    public function find(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:tags,id,is_deleted,0']);
        // get one tag
        $tag = Tag::where('id', $id)->where('is_deleted', 0)->first();
        if (!$tag) return res_fail('Tag not found.', [], 1, 404);
        return res_success('Get one tag successful.', new TagResource($tag));
    }
}
