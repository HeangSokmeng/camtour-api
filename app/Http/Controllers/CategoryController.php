<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\UserService;

class CategoryController extends Controller
{
    public function store(Request $req)
    {
        // validate
        $req->validate([
            'name' => 'required|string|max:250',
            'name_km' => 'required|string|max:250',
            'description' => 'nullable|string|max:65530',
            'image' => 'nullable|image|mimetypes:image/png,image/jpeg|max:2048',
        ]);
        // store new category
        $image = Category::DEFAULT_IMAGE;
        $category = new Category($req->only(['name', 'description', 'name_km']));
        if ($req->hasFile('image')) {
            $file = $req->file('image');
            $image = $file->store('categories', ['disk' => 'public']);
        }
        $category->image = $image;
        // Set user info
        $user = UserService::getAuthUser($req);
        $category->create_uid = $user->id;
        $category->update_uid = $user->id;
        $category->save();
        return res_success('Store new category successful.', new CategoryResource($category));
    }

    public function index(Request $req)
    {
        // validation
        $req->validate([
            'search' => 'nullable|string|max:50'
        ]);
        $categories = new Category();
        if ($req->filled('search')) {
            $s = $req->input('search');
            $categories = $categories->where(function ($q) use ($s) {
                $q->where('id', $s)
                   ->orWhere('name', 'like', "%$s%");
            });
        }
        $categories = $categories->where('is_deleted', 0)->orderBy('name', 'asc')->get();
        return res_success('Get all categories successful.', CategoryResource::collection($categories));
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:categories,id,is_deleted,0',
            'name' => 'nullable|string|max:250',
            'name_km' => 'nullable|string|max:250',
            'description' => 'nullable|string|max:65530',
            'image' => 'nullable|image|mimetypes:image/jpeg,image/png|max:2048',
        ]);
        // find category
        $category = Category::where('id', $id)->where('is_deleted', 0)->first();
        if (!$category) return res_fail('Category not found.', [], 1, 404);
        // update category
        if ($req->filled('name')) {
            $category->name = $req->input('name');
        }if ($req->filled('name_km')) {
            $category->name_km = $req->input('name_km');
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
        $user = UserService::getAuthUser($req);
        $category->update_uid = $user->id;
        $category->save();
        return res_success('Update category successful.', new CategoryResource($category));
    }

    public function find(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:categories,id,is_deleted,0'
        ]);
        // get one category
        $category = Category::where('id', $id)->where('is_deleted', 0)->first();
        if (!$category) return res_fail('Category not found.', [], 1, 404);
        return res_success('Get one category successful.', new CategoryResource($category));
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:categories,id,is_deleted,0'
        ]);
        // find category
        $category = Category::where('id', $id)->where('is_deleted', 0)->first();
        if (!$category) return res_fail('Category not found.', [], 1, 404);
        // Before soft delete, handle image if needed
        if ($category->image != Category::DEFAULT_IMAGE) {
            Storage::disk('public')->delete($category->image);
            $category->image = Category::DEFAULT_IMAGE;
        }
        // Soft delete
        $user = UserService::getAuthUser($req);
        $category->update([
            'is_deleted' => 1,
            'deleted_uid' => $user->id,
            'deleted_datetime' => now()
        ]);
        return res_success('Delete category successful.');
    }

    public function destroyImage(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:categories,id,is_deleted,0'
        ]);
        // find category
        $category = Category::where('id', $id)->where('is_deleted', 0)->first();
        if (!$category) return res_fail('Category not found.', [], 1, 404);
        // delete image
        if ($category->image != Category::DEFAULT_IMAGE) {
            Storage::disk('public')->delete($category->image);
        }
        $category->image = Category::DEFAULT_IMAGE;
        $category->save();
        return res_success('Reset image category successful.', new CategoryResource($category));
    }
}
