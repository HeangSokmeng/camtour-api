<?php

namespace App\Http\Controllers;

use App\Models\LocationStar;
use App\Models\Role;
use Illuminate\Http\Request;

class LocationStarController extends Controller
{
    public function store(Request $req, $id)
    {
        // validation
        $req->merge(['id' => $id]);
        $req->validate([
            'id' => 'required|integer|min:1|exists:locations,id',
            'star' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:250'
        ]);
        $req->merge(['comment' => htmlspecialchars($req->input('comment'))]);
        $loginUser = $req->user('sanctum');
        // store rating
        $star = new LocationStar($req->only(['star', 'comment']));
        $star->rater_id = $loginUser->id;
        $star->location_id = $id;
        $star->save();
        // response back
        return res_success('Store location review successful.');
    }

    public function destroy(Request $req, $reviewId)
    {
        // validation
        $req->merge(['id' => $reviewId]);
        $req->validate(['id' => 'required|integer|min:1|exists:location_stars,id']);
        $loginUser = $req->user('sanctum');
        // check if owner of review
        $star = LocationStar::where('id', $reviewId)->first();
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
            'id' => 'required|integer|min:1|exists:location_stars,id',
            'star' => 'nullable|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:250'
        ]);
        $loginUser = $req->user('sanctum');
        // check if user is owner of this review
        $star = LocationStar::where('id', $reviewId)->first();
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
}
