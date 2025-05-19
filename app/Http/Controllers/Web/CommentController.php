<?php

namespace App\Http\Controllers\Web;

use ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\LocationStar;
use App\Services\UserService;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $req)
    {
        $req->validate([
            "location_id" => "required|integer|min:1|exists:locations,id",
            "comment" => "nullable|string|max:5000",
            "star" => "nullable|numeric|min:1|max:5",
            "status" => "sometimes|boolean"
        ]);

        $user = UserService::getAuthUser($req);

        $locationStar = new LocationStar([
            'rater_id' => $user->id,
            'location_id' => $req->location_id,
            'star' => $req->star,
            'comment' => $req->comment,
            'status' => $req->has('status') ? $req->status : true,
            'create_uid' => $user->id,
            'update_uid' => $user->id,
        ]);

        $locationStar->save();

        return res_success('Rating submitted successfully.');
    }

    public function update(Request $req, $id)
    {
        $locationStar = LocationStar::where('is_deleted', 0)->find($id);
        if (!$locationStar) return ApiResponse::NotFound('Rating not found');

        $req->validate([
            "location_id" => "sometimes|integer|min:1|exists:locations,id",
            "comment" => "nullable|string|max:5000",
            "star" => "sometimes|numeric|min:1|max:5",
            "status" => "sometimes|boolean"
        ]);

        if ($req->has('location_id')) $locationStar->location_id = $req->location_id;
        if ($req->has('comment')) $locationStar->comment = $req->comment;
        if ($req->has('star')) $locationStar->star = $req->star;
        if ($req->has('status')) $locationStar->status = $req->status;

        $user = UserService::getAuthUser($req);
        $locationStar->update_uid = $user->id;
        $locationStar->save();

        return res_success('Rating updated successfully.');
    }

    public function getOne(Request $req, $id)
    {
        $rating = LocationStar::with('rater', 'location')
            ->where('id', $id)
            ->where('is_deleted', 0)
            ->first();
        if (!$rating) return ApiResponse::NotFound('Rating not found');
        $rating->rater_name = $rating->rater->first_name . ' ' . $rating->rater->last_name;
        $rating->location_name = $rating->location->name;
        unset($rating->rater, $rating->location);
        return res_success('Rating details retrieved successfully', $rating);
    }

    public function getAll(Request $req)
    {
        $query = LocationStar::with(['rater', 'location'])
            ->where('is_deleted', 0);
        if ($req->has('status'))   $query->where('status', (bool)$req->status);
        if ($req->has('location_name')) {
            $query->whereHas('location', function ($q) use ($req) {
                $q->where('name', 'like', '%' . $req->location_name . '%');
            });
        }
        $ratings = $query->orderByDesc('id')->get();
        foreach ($ratings as $rating) {
            $rating->rater_name = $rating->rater->first_name . ' ' . $rating->rater->last_name;
            $rating->location_name = $rating->location->name;
            unset($rating->rater, $rating->location);
        }
        return res_success("All ratings retrieved successfully.", $ratings);
    }



    public function toggleStatus(Request $req, $id)
    {
        $rating = LocationStar::where('is_deleted', 0)->find($id);
        if (!$rating) return ApiResponse::NotFound('Rating not found');

        $rating->status = !$rating->status;
        $rating->save();

        $message = $rating->status ? 'Rating has been activated.' : 'Rating has been deactivated.';
        return res_success($message);
    }

    public function destroy(Request $req, $id)
    {
        $rating = LocationStar::where('is_deleted', 0)->find($id);
        if (!$rating) return ApiResponse::NotFound('Rating not found');

        $user = UserService::getAuthUser($req);
        $rating->is_deleted = 1;
        $rating->deleted_uid = $user->id;
        $rating->delete_notes = $req->delete_notes ?? null;
        $rating->save();

        return res_success('Rating deleted successfully.');
    }
    public function lockComment(Request $req, $id)
    {
        $rating = LocationStar::where('is_deleted', 0)->find($id);
        if (!$rating)  return ApiResponse::NotFound('Rating not found');
        $rating->status = !$rating->status;
        $rating->save();
        $message = $rating->status ? 'Rating has been unlocked (active).' : 'Rating has been locked (inactive).';
        return res_success($message);
    }
}
