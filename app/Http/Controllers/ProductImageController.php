<?php

namespace App\Http\Controllers;

use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\UserService;
use App\Http\Resources\ProductImageResource;

class ProductImageController extends Controller
{
    public function store(Request $req)
    {
        // validation
        $req->validate([
            "product_id" => "required|integer|min:1|exists:products,id,is_deleted,0",
            "image" => "required|file|mimetypes:image/png,image/jpeg|max:2048"
        ]);

        // store image first
        $image = $req->file("image")->store("products", ["disk" => "public"]);

        // store product img & response
        $productImage = new ProductImage($req->only(["product_id"]));
        $productImage->image = $image;

        // Set user info
        $user = UserService::getAuthUser($req);
        $productImage->create_uid = $user->id;
        $productImage->update_uid = $user->id;

        $productImage->save();
        return res_success("Store new product image success.", new ProductImageResource($productImage));
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:product_images,id,is_deleted,0',
        ]);

        // find product image
        $image = ProductImage::where('id', $id)->where('is_deleted', 0)->first();
        if (!$image) return res_fail('Product image not found.', [], 1, 404);

        // delete image file from storage
        Storage::disk('public')->delete($image->image);

        // soft delete
        $user = UserService::getAuthUser($req);
        $image->update([
            'is_deleted' => 1,
            'deleted_uid' => $user->id,
            'deleted_datetime' => now()
        ]);

        return res_success("Delete image success.");
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(["id" => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:product_images,id,is_deleted,0',
            "image" => "required|file|mimetypes:image/png,image/jpeg|max:2048"
        ]);

        // find product image
        $image = ProductImage::where("id", $id)->where('is_deleted', 0)->first();
        if (!$image) return res_fail('Product image not found.', [], 1, 404);

        // Set user info
        $user = UserService::getAuthUser($req);
        $image->update_uid = $user->id;

        // save new image & delete old image
        $newImg = $req->file("image")->store("products", ["disk" => "public"]);
        Storage::disk("public")->delete($image->image);
        $image->image = $newImg;
        $image->save();

        return res_success("Update image success.", new ProductImageResource($image));
    }

    public function find(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:product_images,id,is_deleted,0']);

        // get product image
        $image = ProductImage::where('id', $id)->where('is_deleted', 0)->with('product:id,name')->first();
        if (!$image) return res_fail('Product image not found.', [], 1, 404);

        return res_success('Get product image successful.', new ProductImageResource($image));
    }
}
