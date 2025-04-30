<?php

namespace App\Http\Controllers;

use App\Http\Resources\Product\ProductDetailResource;
use App\Http\Resources\Product\ProductIndexResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Storage;

class ProductController extends Controller
{
    public function store(Request $req)
    {
        // validation
        $req->validate([
            'name' => 'required|string|max:250',
            'name_km' => 'required|string|max:250',
            'code' => 'required|string|max:250|unique:products,code',
            'description' => 'nullable|string|max:65530',
            'price' => 'required|numeric|min:0',
            'status' => 'required|string|in:drafting,published',
            'thumbnail' => 'nullable|file|mimetypes:image/png,image/jpeg|max:2048',
            'brand_id' => 'nullable|integer|min:1|exists:brands,id',
            'category_id' => 'nullable|integer|min:1|exists:categories,id',
            'product_category_id' => 'nullable|integer|min:1|exists:product_categories,id',
        ]);
        $req->merge(['description' => htmlspecialchars($req->input('description'))]);

        // store thumbnail
        $thumbnail = Product::DEFAULT_THUMBNAIL;
        if ($req->hasFile('thumbnail')) {
            $thumbnail = $req->file('thumbnail')->store('products', ['disk' => 'public']);
        }

        // store product & reponse back
        $product = new Product($req->only(['name', 'name_km', 'code', 'description', 'price', 'status', 'category_id', 'product_category_id', 'brand_id']));
        $product->thumbnail = $thumbnail;
        $product->save();
        return res_success('Store new product successful');
    }

    public function index(Request $req)
    {
        // validation
        $req->validate([
            'search' => 'nullable|string|max:50',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
        ]);

        // setup default value
        $perPage = $req->filled('per_page') ? intval($req->input('per_page')) : 15;

        // get product
        $products = new Product();
        if ($req->filled('search')) {
            $s = $req->input('search');
            $products = $products
            ->with(['brand', 'pcategory'])
            ->where(function ($q) use ($s) {
                $q->where('name', "like", "%$s%")
                    ->orWhere("name_km", "like", "%$s%");
            });
        }
        $products = $products->where('status', 'published')->orderByDesc("id")->paginate($perPage);
        return res_paginate($products, "Get all prodcut success", ProductIndexResource::collection($products));
    }

    public function find(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:products,id']);

        // get product
        $product = Product::where('id', $id)
            ->where('status', 'published')
            ->with(['brand', 'category', 'pcategory', 'colors', 'sizes', 'tags', 'images', 'variants'])
            ->first();
        return res_success("Get detail product success.", new ProductDetailResource($product));
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:products,id'
        ]);

        // delete thumbnail first
        $product = Product::where('id', $id)->first(['id', 'thumbnail']);
        if ($product->thumbnail != Product::DEFAULT_THUMBNAIL) {
            Storage::disk('public')->delete("products/{$product->thumbnail}");
        }

        // delete product & response
        $product->delete();
        return res_success('Delete product successful.');
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:products,id',
            'name' => 'required|string|max:250',
            'name_km' => 'required|string|max:250',
            'code' => "required|string|max:250|unique:products,code,$id",
            'description' => 'nullable|string|max:65530',
            'price' => 'required|numeric|min:0',
            'status' => 'required|string|in:drafting,published',
            'thumbnail' => 'nullable|file|mimetypes:image/png,image/jpeg|max:2048',
            'brand_id' => 'nullable|integer|min:1|exists:brands,id',
            'category_id' => 'nullable|integer|min:1|exists:categories,id',
            'product_category_id' => 'nullable|integer|min:1|exists:product_categories,id',
        ]);
        $req->merge(['description' => htmlspecialchars($req->input('description'))]);
        $dataUpdate = $req->only(['name', 'name_km', 'code', 'description', 'price', 'status', 'brand_id', 'category_id', 'product_category_id']);

        // update thumbnail first
        $product = Product::where('id', $id)->first(['thumbnail']);
        if ($req->hasFile('thumbnail')) {
            $thumbnail = $req->file('thumbnail')->store('products', ['disk' => 'public']);
            if ($product->thumbnail != Product::DEFAULT_THUMBNAIL) {
                Storage::disk('public')->delete("products/{$product->thumbnail}");
            }
            $dataUpdate["thumbnail"] = $thumbnail;
        }

        // update product & response
        Product::where("id", $id)->update($dataUpdate);
        return res_success("Update product info success.");
    }
}
