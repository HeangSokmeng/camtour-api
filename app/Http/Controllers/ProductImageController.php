<?php

namespace App\Http\Controllers;

use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function store(Request $req)
    {
        // validation
        $req->validate([
            "product_id" => "required|integer|min:1|exists:products,id",
            "image" => "required|file|mimetypes:image/png,image/jpeg|max:2048"
        ]);

        // store image first
        $image = $req->file("image")->store("products", ["disk" => "public"]);

        // store product img & response
        $productImage = new ProductImage($req->only(["product_id"]));
        $productImage->image = $image;
        $productImage->save();
        return res_success("Store new product image success.");
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:product_images,id',
        ]);

        // delete image file & response
        $image = ProductImage::where('id', $id)->first(['id', 'image']);
        Storage::disk('public')->delete($image->image);
        $image->delete();
        return res_success("Delete image success.");
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(["id" => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:product_images,id',
            "image" => "required|file|mimetypes:image/png,image/jpeg|max:2048"
        ]);

        // save new image & delete old image
        $newImg = $req->file("image")->store("products", ["disk" => "public"]);
        $image = ProductImage::where("id", $id)->first(["id", "image"]);
        Storage::disk("public")->delete($image->image);
        $image->image = $newImg;
        $image->save();
        return res_success("Update image success.");
    }
}
