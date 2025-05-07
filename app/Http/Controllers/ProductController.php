<?php

namespace App\Http\Controllers;

use App\Http\Resources\Product\ProductDetailResource;
use App\Http\Resources\Product\ProductIndexResource;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function store(Request $req)
    {
        // validation
        $req->validate([
            'name' => 'required|string|max:250',
            'name_km' => 'required|string|max:250',
            'code' => 'required|string|max:250|unique:products,code,NULL,id,is_deleted,0',
            'description' => 'nullable|string|max:65530',
            'price' => 'required|numeric|min:0',
            'status' => 'required|string|in:drafting,published',
            'thumbnail' => 'nullable|file|mimetypes:image/png,image/jpeg|max:2048',
            'brand_id' => 'nullable|integer|min:1|exists:brands,id,is_deleted,0',
            'category_id' => 'nullable|integer|min:1|exists:categories,id,is_deleted,0',
            'product_category_id' => 'nullable|integer|min:1|exists:product_categories,id,is_deleted,0',
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

        // Set user info
        $user = UserService::getAuthUser($req);
        $product->create_uid = $user->id;
        $product->update_uid = $user->id;

        $product->save();
        return res_success('Store new product successful', new ProductDetailResource($product));
    }

    public function index(Request $req)
    {
        // validation
        $req->validate([
            'search' => 'nullable|string|max:50',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'category_id' => 'nullable|integer|exists:product_categories,id',
        ]);
        // setup default value
        $perPage = $req->filled('per_page') ? intval($req->input('per_page')) : 15;
        // get product
        $products = Product::with(['brand', 'pcategory'])
            ->where('is_deleted', 0)
            ->where('status', 'published');
        // filter by search
        if ($req->filled('search')) {
            $s = $req->input('search');
            $products->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                ->orWhere('name_km', 'like', "%$s%");
            });
        }
        // filter by brand
        if ($req->filled('brand_id')) {
            $products->where('brand_id', $req->input('brand_id'));
        }
        // filter by category
        if ($req->filled('category_id')) {
            $products->where('product_category_id', $req->input('category_id'));
        }
        // paginate
        $products = $products->orderByDesc('id')->paginate($perPage);

        return res_paginate($products, "Get all product success", ProductIndexResource::collection($products));
    }

    public function find(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:products,id,is_deleted,0']);

        // get product
        $product = Product::where('id', $id)
            ->where('is_deleted', 0)
            ->where('status', 'published')
            ->with(['brand', 'category', 'pcategory', 'colors', 'sizes', 'tags', 'images', 'variants'])
            ->first();
        if (!$product) return res_fail('Product not found or not published.', [], 1, 404);
        return res_success("Get detail product success.", new ProductDetailResource($product));
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:products,id,is_deleted,0'
        ]);
        // find product
        $product = Product::where('id', $id)->where('is_deleted', 0)->first(['id', 'thumbnail']);
        if (!$product) return res_fail('Product not found.', [], 1, 404);
        // handle thumbnail if needed
        if ($product->thumbnail != Product::DEFAULT_THUMBNAIL) {
            Storage::disk('public')->delete($product->thumbnail);
            $product->thumbnail = Product::DEFAULT_THUMBNAIL;
        }

        // soft delete product & response
        $user = UserService::getAuthUser($req);
        $product->update([
            'is_deleted' => 1,
            'deleted_uid' => $user->id,
            'deleted_datetime' => now()
        ]);

        return res_success('Delete product successful.');
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:products,id,is_deleted,0',
            'name' => 'required|string|max:250',
            'name_km' => 'required|string|max:250',
            'code' => "required|string|max:250|unique:products,code,$id,id,is_deleted,0",
            'description' => 'nullable|string|max:65530',
            'price' => 'required|numeric|min:0',
            'status' => 'required|string|in:drafting,published',
            'thumbnail' => 'nullable|file|mimetypes:image/png,image/jpeg|max:2048',
            'brand_id' => 'nullable|integer|min:1|exists:brands,id,is_deleted,0',
            'category_id' => 'nullable|integer|min:1|exists:categories,id,is_deleted,0',
            'product_category_id' => 'nullable|integer|min:1|exists:product_categories,id,is_deleted,0',
        ]);

        // find product
        $product = Product::where('id', $id)->where('is_deleted', 0)->first();
        if (!$product) return res_fail('Product not found.', [], 1, 404);

        // Set user info
        $user = UserService::getAuthUser($req);
        $product->update_uid = $user->id;

        $req->merge(['description' => htmlspecialchars($req->input('description'))]);

        // update product fields
        if ($req->filled('name')) {
            $product->name = $req->input('name');
        }
        if ($req->filled('name_km')) {
            $product->name_km = $req->input('name_km');
        }
        if ($req->filled('code')) {
            $product->code = $req->input('code');
        }
        if ($req->has('description')) {
            $product->description = $req->input('description');
        }
        if ($req->filled('price')) {
            $product->price = $req->input('price');
        }
        if ($req->filled('status')) {
            $product->status = $req->input('status');
        }
        if ($req->filled('brand_id')) {
            $product->brand_id = $req->input('brand_id');
        }
        if ($req->filled('category_id')) {
            $product->category_id = $req->input('category_id');
        }
        if ($req->filled('product_category_id')) {
            $product->product_category_id = $req->input('product_category_id');
        }

        // update thumbnail if needed
        if ($req->hasFile('thumbnail')) {
            $thumbnail = $req->file('thumbnail')->store('products', ['disk' => 'public']);
            if ($product->thumbnail != Product::DEFAULT_THUMBNAIL) {
                Storage::disk('public')->delete($product->thumbnail);
            }
            $product->thumbnail = $thumbnail;
        }

        // save product & response
        $product->save();
        return res_success("Update product info success.", new ProductDetailResource($product));
    }
}
