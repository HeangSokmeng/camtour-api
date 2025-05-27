<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use App\Services\UserService;

class ProductCategoryController extends Controller
{
    public function store(Request $req)
    {
        $req->validate([
            "name" => "required|string|max:250|unique:product_categories,name,NULL,id,is_deleted,0",
            "name_km" => "nullable|string|max:250"
        ]);
        $category = new ProductCategory($req->only(["name", "name_km"]));
        $user = UserService::getAuthUser($req);
        $category->create_uid = $user->id;
        $category->update_uid = $user->id;
        $category->save();
        return res_success("Store category successful.", new ProductCategoryResource($category));
    }

    public function index(Request $req)
    {
        $req->validate([
            'search' => 'nullable|string|max:50'
        ]);
        $categories = new ProductCategory();
        $categories = $categories->where('is_deleted', 0);
        if ($req->filled('search')) {
            $s = $req->input('search');
            $categories = $categories->where(function ($q) use ($s) {
                $q->where('name', 'ilike', "%$s%");
                $q->orWhere('name_km', 'ilike', "%$s%");

            });
        }
        $categories = $categories->orderBy("name")->get();
        return res_success("Get all product categories success.", ProductCategoryResource::collection($categories));
    }

    public function find(Request $req, $id)
    {
        $req->merge(["id" => $id]);
        $req->validate([
            "id" => "required|integer|min:1|exists:product_categories,id,is_deleted,0"
        ]);
        $category = ProductCategory::where("id", $id)->where('is_deleted', 0)->first();
        if (!$category) return res_fail('Product category not found.', [], 1, 404);
        return res_success("Get product category success.", new ProductCategoryResource($category));
    }

    public function destroy(Request $req, $id)
    {
        $req->merge(["id" => $id]);
        $req->validate([
            "id" => "required|integer|min:1|exists:product_categories,id,is_deleted,0"
        ]);
        $category = ProductCategory::where("id", $id)->where('is_deleted', 0)->first();
        if (!$category) return res_fail('Product category not found.', [], 1, 404);
        $user = UserService::getAuthUser($req);
        $category->update([
            'is_deleted' => 1,
            'deleted_uid' => $user->id,
            'deleted_datetime' => now()
        ]);
        return res_success("Delete product category success.");
    }

    public function update(Request $req, $id)
    {
        $req->merge(["id" => $id]);
        $req->validate([
            "id" => "required|integer|min:1|exists:product_categories,id,is_deleted,0",
            "name" => "required|string|max:250|unique:product_categories,name,$id,id,is_deleted,0",
            "name_km" => "nullable|string|max:250"
        ]);
        $category = ProductCategory::where("id", $id)->where('is_deleted', 0)->first();
        if (!$category) return res_fail('Product category not found.', [], 1, 404);
        $user = UserService::getAuthUser($req);
        $category->update_uid = $user->id;
        if ($req->filled('name')) {
            $category->name = $req->input('name');
        }
        $category->save();
        return res_success("Update product category success.", new ProductCategoryResource($category));
    }
}
