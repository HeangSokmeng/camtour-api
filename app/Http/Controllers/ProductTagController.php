<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductTagController extends Controller
{
    public function sync(Request $req)
    {
        // validation
        $req->merge(["tag_ids" => json_decode($req->input("tag_ids")) ?? []]);
        $req->validate([
            "product_id" => "required|integer|min:1|exists:products,id",
            "tag_ids" => "required|array",
            "tag_ids.*" => "integer|min:1|exists:tags,id"
        ]);
        // update product tag
        $product = Product::where("id", $req->input("product_id"))->first(["id"]);
        $product->tags()->sync($req->input("tag_ids"));
        return res_success("Sync tag successful");
    }
}
