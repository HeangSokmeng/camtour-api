<?php

namespace App\Http\Controllers;

use ApiResponse;
use App\Models\ProductSize;
use App\Services\UserService;
use Illuminate\Http\Request;

class ProductSizeController extends Controller
{
    public function store(Request $req)
    {
        // validation
        $req->validate([
            'product_id' => 'required|integer|min:1|exists:products,id',
            'size' => 'required|string|max:250',
        ]);

        // store new product size
        $product = new ProductSize($req->only(["product_id", "size"]));
        $product->save();
        return res_success("Store new product size success.");
    }

    public function update(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:product_sizes,id',
            'product_id' => 'required|integer|min:1|exists:products,id',
            'size' => 'required|string|max:250',
        ]);

        // update prodcut size
        ProductSize::where('id', $req->id)->update($req->only(['product_id', 'size']));
        return res_success("Update product size success.");
    }

    public function destroy(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:product_sizes,id',
        ]);

        // delete product size
        ProductSize::where('id', $id)->delete();
        return res_success('Delete product size success.');
    }
}
