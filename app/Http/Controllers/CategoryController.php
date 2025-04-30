<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function store(Request $req)
    {
        // validate
        $req->validate([
            'name' => 'required|string|max:250',
            'description' => 'nullable|string|max:65530',
            'image' => 'nullable|image|mimetypes:image/png,image/jpeg|max:2048',
        ]);

        // store new category
        $image = Category::DEFAULT_IMAGE;
        $category = new Category($req->only(['name', 'description']));
        if ($req->hasFile('image')) {
            $file = $req->file('image');
            $image = $file->store('categories', ['disk' => 'public']);
        }
        $category->image = $image;
        $category->save();
        return res_success('Store new category successful.', new CategoryResource($category));
    }

    public function index(Request $req)
    {
        // validation
        $req->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'sort_col' => 'nullable|string|in:id,name',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'search' => 'nullable|string|max:50',
        ]);

        // setup default data
        $perPage = $req->filled('per_page') ? intval($req->input('per_page')) : 50;
        $sortCol = $req->filled('sort_col') ? $req->input('sort_col') : 'name';
        $sortDir = $req->filled('sort_dir') ? $req->input('sort_dir') : 'asc';
        $search = $req->filled('search') ? $req->input('search') : '';

        // build query & response
        $categories = new Category();
        if (strlen($search) > 0) {
            $categories = $categories->where('id', $search)
                ->orWhere('name', 'like', "%$search%");
        }
        $categories = $categories->orderBy($sortCol, $sortDir)->paginate($perPage);
        return res_paginate($categories, 'Get all categories successful.', CategoryResource::collection($categories));
    }

    public function update(Request $req, $id)
    {
        // validate
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:categories,id',
            'name' => 'nullable|string|max:250',
            'description' => 'nullable|string|max:65530',
            'image' => 'nullable|image|mimetypes:image/jpeg,image/png|max:2048',
        ]);

        // update category
        $category = Category::where('id', $id)->first();
        if ($req->filled('name')) {
            $category->name = $req->input('name');
        }
        if ($req->has('description')) {
            $category->description = $req->input('description');
        }
        if ($req->hasFile('image')) {
            $file = $req->file('image');
            $imageName = $file->store('categories', ['disk' => 'public']);
            if ($category->image != Category::DEFAULT_IMAGE) {
                Storage::disk('public')->delete($category->image);
            }
            $category->image = $imageName;
        }
        $category->save();
        return res_success('Update category successful.', new CategoryResource($category));
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id'=> $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:categories,id']);

        // delete image & data
        $category = Category::where('id', $id)->first();
        if ($category->image != Category::DEFAULT_IMAGE) {
            Storage::disk('public')->delete($category->image);
        }
        $category->delete();
        return res_success('Delete category successful.');
    }

    public function destroyImage(Request $req, $id)
    {
        // validation
        $req->merge(['id'=> $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:categories,id']);

        // delete image
        $category = Category::where('id', $id)->first();
        if ($category->image != Category::DEFAULT_IMAGE) {
            Storage::disk('public')->delete($category->image);
        }
        $category->image = Category::DEFAULT_IMAGE;
        $category->save();
        return res_success('Reset image category successful.', new CategoryResource($category));
    }
}
