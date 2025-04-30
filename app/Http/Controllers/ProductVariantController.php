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
        ]);

        // setup default value
        $perPage = $req->input('per_page', 15);

        $products = ProductVariant::with(['product.brand', 'color'])
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
            ->orderByDesc('id')
            ->paginate($perPage);

        return res_paginate($products, "Get all product variants success", ProductVariantResource::collection($products));
    }
        public function show($id)
        {
            $variant = ProductVariant::with(['product.brand', 'color', 'size'])->find($id);
            if (!$variant) {
                return res_fail("Product variant not found", 404);
            }
            return res_success( "Product variant detail", new ProductVariantResource($variant),);
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
