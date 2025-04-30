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
            'sort_col' => 'nullable|string|in:id,name',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'search' => 'nullable|string|max:50'
        ]);

        // Set up default values
        $perPage = $req->input('per_page', 50);
        $sortCol = $req->input('sort_col', 'size');
        $sortDir = $req->input('sort_dir', 'asc');
        $search = $req->input('search', '');

        // Build query
        $query = ProductSize::query()->with('product:id,name');
        if (!empty($search)) {
            $query->where('id', $search)
                ->orWhere('size', 'like', "%$search%");
        }
        $productSizes = $query->orderBy($sortCol, $sortDir)->paginate($perPage);
        return res_paginate($productSizes, "Get all prodcut color success", ProductSizeResource::collection($productSizes));
    }
    public function store(Request $req)
    {
        // validation
        $req->validate([
            'product_id' => 'required|integer|min:1|exists:products,id',
            'size' => 'required|string|max:250',
        ]);

        // store new product size
        $product = new ProductSize($req->only(["product_id", "size"]));
        $product->save();
        return res_success("Store new product size success.");
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:product_sizes,id',
            'product_id' => 'required|integer|min:1|exists:products,id',
            'size' => 'required|string|max:250',
        ]);

        // update prodcut size
        ProductSize::where('id', $req->id)->update($req->only(['product_id', 'size']));
        return res_success("Update product size success.");
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:product_sizes,id',
        ]);

        // delete product size
        ProductSize::where('id', $id)->delete();
        return res_success('Delete product size success.');
    }
}
