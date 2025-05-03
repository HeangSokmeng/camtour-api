<?php

namespace App\Http\Controllers;

use ApiResponse;
use App\Http\Resources\Product\ProductSizeResource;
use App\Models\ProductSize;
use App\Services\UserService;
use Illuminate\Http\Request;

class ProductSizeController extends Controller
{
    public function index(Request $req)
    {
        // Validate request parameters
        $req->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'sort_col' => 'nullable|string|in:id,size',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'search' => 'nullable|string|max:50'
        ]);

        // Set up default values
        $perPage = $req->input('per_page', 50);
        $sortCol = $req->input('sort_col', 'size');
        $sortDir = $req->input('sort_dir', 'asc');
        $search = $req->input('search', '');

        // Build query
        $query = ProductSize::query()->where('is_deleted', 0)->with('product:id,name');
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('id', $search)
                  ->orWhere('size', 'like', "%$search%");
            });
        }
        $productSizes = $query->orderBy($sortCol, $sortDir)->paginate($perPage);
        return res_paginate($productSizes, "Get all product sizes success", ProductSizeResource::collection($productSizes));
    }

    public function store(Request $req)
    {
        // validation
        $req->validate([
            'product_id' => 'required|integer|min:1|exists:products,id,is_deleted,0',
            'size' => 'required|string|max:250',
        ]);

        // store new product size
        $productSize = new ProductSize($req->only(["product_id", "size"]));
        $user = UserService::getAuthUser($req);
        $productSize->create_uid = $user->id;
        $productSize->update_uid = $user->id;
        $productSize->save();
        return res_success("Store new product size success.", new ProductSizeResource($productSize));
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:product_sizes,id,is_deleted,0',
            'product_id' => 'nullable|integer|min:1|exists:products,id,is_deleted,0',
            'size' => 'nullable|string|max:250',
        ]);

        // update product size
        $productSize = ProductSize::where('id', $id)->where('is_deleted', 0)->first();
        if (!$productSize) return res_fail('Product size not found.', [], 1, 404);

        $user = UserService::getAuthUser($req);
        $productSize->update_uid = $user->id;

        if ($req->filled('product_id')) {
            $productSize->product_id = $req->input('product_id');
        }
        if ($req->filled('size')) {
            $productSize->size = $req->input('size');
        }

        $productSize->save();
        return res_success("Update product size success.", new ProductSizeResource($productSize));
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:product_sizes,id,is_deleted,0',
        ]);

        // find product size
        $productSize = ProductSize::where('id', $id)->where('is_deleted', 0)->first();
        if (!$productSize) return res_fail('Product size not found.', [], 1, 404);

        // soft delete
        $user = UserService::getAuthUser($req);
        $productSize->update([
            'is_deleted' => 1,
            'deleted_uid' => $user->id,
            'deleted_datetime' => now()
        ]);

        return res_success('Delete product size success.');
    }

    public function find(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:product_sizes,id,is_deleted,0']);

        // get product size
        $productSize = ProductSize::where('id', $id)->where('is_deleted', 0)->with('product:id,name')->first();
        if (!$productSize) return res_fail('Product size not found.', [], 1, 404);

        return res_success('Get product size successful.', new ProductSizeResource($productSize));
    }
}
