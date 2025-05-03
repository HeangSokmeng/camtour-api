<?php

namespace App\Http\Controllers;

use ApiResponse;
use App\Http\Resources\Product\ProductColorResource;
use App\Models\Product;
use App\Models\ProductColor;
use App\Services\UserService;
use Illuminate\Http\Request;

class ProductColorController extends Controller
{
    public function index(Request $req)
    {
        // Validate request parameters
        $req->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'sort_col' => 'nullable|string|in:id,name',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'search' => 'nullable|string|max:50'
        ]);

        // Set up default values
        $perPage = $req->input('per_page', 50);
        $sortCol = $req->input('sort_col', 'name');
        $sortDir = $req->input('sort_dir', 'asc');
        $search = $req->input('search', '');

        // Build query
        $query = ProductColor::query()->where('is_deleted', 0)->with('product:id,name');
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('id', $search)
                  ->orWhere('name', 'like', "%$search%");
            });
        }
        $productColors = $query->orderBy($sortCol, $sortDir)->paginate($perPage);
        return res_paginate($productColors, "Get all product color success", ProductColorResource::collection($productColors));
    }

    public function store(Request $req)
    {
        // validation
        $req->validate([
            'product_id' => 'required|integer|min:1|exists:products,id,is_deleted,0',
            'name' => 'required|string|max:250',
            'code' => 'required|string|max:250'
        ]);

        // store product color
        $productColor = new ProductColor($req->only(["product_id", "name", "code"]));
        $user = UserService::getAuthUser($req);
        $productColor->create_uid = $user->id;
        $productColor->update_uid = $user->id;
        $productColor->save();
        return res_success("Add color to product.", new ProductColorResource($productColor));
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:product_colors,id,is_deleted,0',
            'name' => 'required|string|max:250',
            'code' => 'required|string|max:250'
        ]);

        // update product color
        $productColor = ProductColor::where('id', $id)->where('is_deleted', 0)->first();
        if (!$productColor) return res_fail('Product color not found.', [], 1, 404);

        $user = UserService::getAuthUser($req);
        $productColor->update_uid = $user->id;

        if ($req->filled('name')) {
            $productColor->name = $req->input('name');
        }
        if ($req->filled('code')) {
            $productColor->code = $req->input('code');
        }

        $productColor->save();
        return res_success('Update product color success.', new ProductColorResource($productColor));
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:product_colors,id,is_deleted,0',
        ]);

        // delete product color
        $productColor = ProductColor::where('id', $id)->where('is_deleted', 0)->first();
        if (!$productColor) return res_fail('Product color not found.', [], 1, 404);

        $user = UserService::getAuthUser($req);
        $productColor->update([
            'is_deleted' => 1,
            'deleted_uid' => $user->id,
            'deleted_datetime' => now()
        ]);

        return res_success('Delete product color successful.');
    }

    public function find(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:product_colors,id,is_deleted,0']);

        // get product color
        $productColor = ProductColor::where('id', $id)->where('is_deleted', 0)->with('product:id,name')->first();
        if (!$productColor) return res_fail('Product color not found.', [], 1, 404);

        return res_success('Get product color successful.', new ProductColorResource($productColor));
    }
}
