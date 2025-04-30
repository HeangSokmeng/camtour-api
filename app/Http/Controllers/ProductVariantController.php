<?php

namespace App\Http\Controllers;

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
