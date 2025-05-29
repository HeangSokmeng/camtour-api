<?php

namespace App\Http\Controllers;

use App\Models\ProductStar;
use App\Models\Role;
use Illuminate\Http\Request;

class ProductStarController extends Controller
{
    public function store(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required',
            'star' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:250'
        ]);
        $req->merge(['comment' => htmlspecialchars($req->input('comment'))]);
        $loginUser = $req->user('sanctum');
        // store rating
        $star = new ProductStar($req->only(['star', 'comment']));
        $star->rater_id = $loginUser->id;
        $star->product_id = $id;
        $star->save();
        // response back
        return res_success('Store location review successful.');
    }

    public function destroy(Request $req, $reviewId)
    {
        // validation
        $req->merge(['id' => $reviewId]);
        $req->validate(['id' => 'required|integer|min:1|exists:product_stars,id']);
        $loginUser = $req->user('sanctum');
        // check if owner of review
        $star = ProductStar::where('id', $reviewId)->first();
        if ($loginUser->role_id != Role::SYSTEM_ADMIN) {
            if ($star->rater_id != $loginUser->id) {
                return res_fail('You are not owner of this review.');
            }
        }
        // delete review & response
        $star->delete();
        return res_success('Store location review successful.');
    }

    public function update(Request $req, $reviewId)
    {
        // validation
        $req->merge(['id' => $reviewId]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:product_stars,id',
            'star' => 'nullable|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:250'
        ]);
        $loginUser = $req->user('sanctum');
        // check if user is owner of this review
        $star = ProductStar::where('id', $reviewId)->first();
        if ($star->rater_id != $loginUser->id) {
            return res_fail('You are not owner of this review.');
        }
        // update review
        if ($req->filled('star')) {
            $star->star = $req->input('star');
        }
        if ($req->filled('comment')) {
            $star->comment = htmlspecialchars($req->input('comment'));
        }
        $star->save();
        // response back
        return res_success('Update review successful');
    }

    function index(Request $req)
    {
        $starProduct = ProductStar::get();
        return res_success("", $starProduct);
    }
}
