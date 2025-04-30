<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function store(Request $req)
    {
        // validation
        $req->validate([
            "name" => "required|string|max:250|unique:product_categories,name"
        ]);

        // store product category
        $category = new ProductCategory($req->only(["name"]));
        $category->save();
        return res_success("Store category successful.");
    }

    public function index(Request $req)
    {
        // get all categories
        $categories = ProductCategory::orderBy("name")->get();
        return res_success("Get all product categories success.", ProductCategoryResource::collection($categories));
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(["id" => $id]);
        $req->validate([
            "id" => "required|integer|min:1|exists:product_categories,id"
        ]);

        // delete product category
        ProductCategory::where("id", $id)->delete();
        return res_success("Delete product category success.");
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(["id" => $id]);
        $req->validate([
            "id" => "required|integer|min:1|exists:product_categories,id",
            "name" => "required|string|max:250|unique:product_categories,name,$id"
        ]);

        // update product category
        ProductCategory::where("id", $id)->update($req->only(["name"]));
        return res_success("Update product category success.");
    }
}
