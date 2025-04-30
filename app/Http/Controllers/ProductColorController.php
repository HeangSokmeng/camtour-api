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
        $query = ProductColor::query()->with('product:id,name');
        if (!empty($search)) {
            $query->where('id', $search)
                ->orWhere('name', 'like', "%$search%");
        }
        $productColors = $query->orderBy($sortCol, $sortDir)->paginate($perPage);
        return res_paginate($productColors, "Get all prodcut color success", ProductColorResource::collection($productColors));
    }


    public function store(Request $req)
    {
        // validation
        $req->validate([
            'product_id' => 'required|integer|min:1|exists:products,id',
            'name' => 'required|string|max:250',
            'code' => 'required|string|max:250'
        ]);

        // store product color
        $productColor = new ProductColor($req->only(["product_id", "name", "code"]));
        $productColor->save();
        return res_success("Add color to product.");
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:product_colors,id',
            'name' => 'required|string|max:250',
            'code' => 'required|string|max:250'
        ]);

        // update product color
        ProductColor::where('id', $id)->update($req->only(['name', 'code']));
        return res_success('Update product color success.');
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:product_colors,id',
        ]);

        // delete product color
        ProductColor::where('id', $id)->delete();
        return res_success('Delete product color successful.');
    }
}
