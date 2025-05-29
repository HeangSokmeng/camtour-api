<?php

namespace App\Http\Controllers;

use App\Http\Resources\Product\ProductVariantResource;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function store(Request $req)
    {
        // add validation
        $req->validate([
            "product_id" => "required|integer|min:1|exists:products,id",
            "product_color_id" => "required|integer|min:1|exists:product_colors,id",
            "product_size_id" => "required|integer|min:1|exists:product_sizes,id",
            "qty" => "required|integer|min:0",
            "price" => "required|numeric|min:0"
        ]);
        // store new product
        $productVariant = new ProductVariant($req->only(['product_id', "product_color_id", "product_size_id", "qty", "price"]));
        $productVariant->save();
        return res_success("Store new product variant.");
    }

    public function index(Request $req)
    {
        // validation
        $req->validate([
            'search' => 'nullable|string|max:50',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'product_id' => 'nullable|integer|exists:products,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'color_id' => 'nullable|integer|exists:product_colors,id',
            'sort_col' => 'nullable|string|in:id,product,color,size,qty,price,brand',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ]);

        // setup default value
        $perPage = $req->input('per_page', 15);
        $sortCol = $req->input('sort_col', 'id');
        $sortDir = $req->input('sort_dir', 'desc');

        $variants = ProductVariant::with(['product.brand', 'color', 'size'])
            // Search functionality
            ->when($req->filled('search'), function ($query) use ($req) {
                $search = $req->input('search');

                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhereHas('brand', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%$search%");
                        });
                })
                    ->orWhereHas('color', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    });
            })
            ->when($req->filled('product_id'), function ($query) use ($req) {
                $query->where('product_id', $req->input('product_id'));
            })
            ->when($req->filled('brand_id'), function ($query) use ($req) {
                $query->where('brand_id', $req->input('brand_id'));
            })
            ->when($req->filled('color_id'), function ($query) use ($req) {
                $query->where('product_color_id', $req->input('color_id'));
            })
            ->when($sortCol === 'id', function ($query) use ($sortDir) {
                $query->orderBy('id', $sortDir);
            })
            ->when($sortCol === 'product', function ($query) use ($sortDir) {
                $query->orderBy(function ($query) {
                    $query->select('name')
                        ->from('products')
                        ->whereColumn('products.id', 'product_variants.product_id')
                        ->limit(1);
                }, $sortDir);
            })
            ->when($sortCol === 'color', function ($query) use ($sortDir) {
                $query->orderBy(function ($query) {
                    $query->select('name')
                        ->from('product_colors')
                        ->whereColumn('product_colors.id', 'product_variants.product_color_id')
                        ->limit(1);
                }, $sortDir);
            })
            ->when($sortCol === 'size', function ($query) use ($sortDir) {
                $query->orderBy(function ($query) {
                    $query->select('size')
                        ->from('product_sizes')
                        ->whereColumn('product_sizes.id', 'product_variants.product_size_id')
                        ->limit(1);
                }, $sortDir);
            })
            ->when($sortCol === 'qty', function ($query) use ($sortDir) {
                $query->orderBy('qty', $sortDir);
            })
            ->when($sortCol === 'price', function ($query) use ($sortDir) {
                $query->orderBy('price', $sortDir);
            })
            ->when($sortCol === 'brand', function ($query) use ($sortDir) {
                $query->orderBy(function ($query) {
                    $query->select('name')
                        ->from('brands')
                        ->whereColumn('brands.id', 'product_variants.brand_id')
                        ->limit(1);
                }, $sortDir);
            })
            ->paginate($perPage);

        return res_paginate($variants, "Get all product variants success", ProductVariantResource::collection($variants));
    }

    public function show($id)
    {
        $variant = ProductVariant::with(['product.brand', 'color', 'size'])->find($id);
        if (!$variant) {
            return res_fail("Product variant not found", 404);
        }
        return res_success("Product variant detail", new ProductVariantResource($variant),);
    }


    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => "required|integer|min:1|exists:product_variants,id",
            "product_id" => "required|integer|min:1|exists:products,id",
            "product_color_id" => "required|integer|min:1|exists:product_colors,id",
            "product_size_id" => "required|integer|min:1|exists:product_sizes,id",
            "qty" => "required|integer|min:0",
            "price" => "required|numeric|min:0"
        ]);

        // update product variant
        ProductVariant::where('id', $id)->update($req->only(['product_id', 'product_color_id', 'product_size_id', 'qty', 'price']));
        return res_success("Update product variant success.");
    }

    public function destory(Request $req, $id)
    {
        // validation
        $req->merge(["id" => $id]);
        $req->validate([
            "id" => "required|integer|min:1|exists:product_variants,id"
        ]);

        // delete variant
        ProductVariant::where("id", $id)->delete();
        return res_success("Destroy product variant success.");
    }
}
