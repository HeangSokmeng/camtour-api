<?php

namespace App\Http\Controllers\Web;

use ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CommentController extends Controller
{
    public function store(Request $req)
    {
        $req->validate([
            "customer_id" => "nullable",
            "location_id" => "required|integer|min:1|exists:locations,id",
            "comment" => "required|string|max:5000",
            "photos" => "sometimes|array",
            "photos.*" => "file|mimetypes:image/png,image/jpeg|max:2048",
            "status" => "sometimes|boolean"
        ]);
        $imagePaths = [];
        if ($req->hasFile('photos')) {
            foreach ($req->file('photos') as $image) {
                $path = $image->store("comments", ["disk" => "public"]);
                $imagePaths[] = $path;
            }
        }
        $comment = new Comment($req->only([
            'location_id',
            'comment',
            'customer_id'
        ]));
        $comment->photos = $imagePaths;
        if (!$req->location_id) return ApiResponse::NotFound('Location not fount');
        $comment->status = $req->has('status') ? $req->status : true;
        $user = UserService::getAuthUser($req);
        $comment->create_uid = $user->id;
        $comment->update_uid = $user->id;
        $comment->customer_id = $user->id;
        $comment->save();
        return res_success('success created.');
    }

    public function update(Request $req, $id)
    {
        $comment = Comment::where('is_deleted', 0)->find($id);
        if (!$comment) return ApiResponse::NotFound('Comment not found');
        $req->validate([
            "location_id" => "sometimes|integer|min:1|exists:locations,id",
            "comment" => "sometimes|string|max:5000",
            "photos" => "sometimes|array",
            "photos.*" => "file|mimetypes:image/png,image/jpeg|max:2048",
            "status" => "sometimes|boolean",
            "remove_photos" => "sometimes|array",
            "remove_photos.*" => "string"
        ]);
        if ($req->has('location_id')) {
            $comment->location_id = $req->location_id;
        }
        if ($req->has('comment')) {
            $comment->comment = $req->comment;
        }
        if ($req->has('status')) {
            $comment->status = $req->status;
        }
        if ($req->has('remove_photos') && is_array($req->remove_photos)) {
            $currentPhotos = $comment->photos;
            $remainingPhotos = [];

            foreach ($currentPhotos as $photo) {
                if (!in_array($photo, $req->remove_photos)) {
                    $remainingPhotos[] = $photo;
                } else {
                    Storage::disk('public')->delete($photo);
                }
            }
            $comment->photos = $remainingPhotos;
        }
        if ($req->hasFile('photos')) {
            $newPhotos = [];
            foreach ($req->file('photos') as $image) {
                $path = $image->store("comments", ["disk" => "public"]);
                $newPhotos[] = $path;
            }
            if (!empty($comment->photos) && is_array($comment->photos)) {
                $comment->photos = array_merge($comment->photos, $newPhotos);
            } else {
                $comment->photos = $newPhotos;
            }
        }
        $user = UserService::getAuthUser($req);
        $comment->update_uid = $user->id;
        $comment->save();
        return res_success('Comment updated successfully.');
    }

    /**
     * Get a single comment by ID
     */
    public function getOneComment(Request $req, $id)
    {
        $comment = Comment::where('id', $id)
            ->where('is_deleted', 0)
            ->first();
        if (!$comment) {
            return ApiResponse::NotFound('Comment not found');
        }
        $comment->load('customer', 'location');
        $comment->commender = $comment->customer->first_name . ' ' . $comment->customer->last_name;
        $comment->location_name = $comment->location->name;
        if (is_array($comment->photos) && !empty($comment->photos)) {
            $comment->photos = array_map(function ($photo) {
                if (filter_var($photo, FILTER_VALIDATE_URL)) {
                    return $photo;
                }
                return url('storage/' . $photo);
            }, $comment->photos);
        }
        unset($comment->customer, $comment->location);

        return res_success('Comment details retrieved successfully', $comment);
    }

    public function getAllComment(Request $req)
    {
        $comments = Comment::query()->with('customer', 'location')
            ->selectRaw('id,customer_id,location_id,comment,photos,status')
            ->where('is_deleted', 0)
            ->get();
        foreach ($comments as $comment) {
            $comment->commender = $comment->customer->first_name . ' ' . $comment->customer->last_name;
            $comment->location_name = $comment->location->name;
            if (is_array($comment->photos) && !empty($comment->photos)) {
                $comment->photos = array_map(function ($photo) {
                    if (filter_var($photo, FILTER_VALIDATE_URL)) {
                        return $photo;
                    }
                    return url('storage/' . $photo);
                }, $comment->photos);
            }
            unset($comment->customer, $comment->location);
        }

        return res_success("Get all comments", $comments);
    }

    public function lockComment(Request $req, $id)
    {
        $comment = Comment::where('is_deleted', 0)->find($id);
        if (!$comment) return ApiResponse::NotFound('user not found');
        $comment->status = !$comment->status;
        $comment->save();
        $message = $comment->status ? 'comment has been unlocked' : 'comment has been locked';
        return res_success($message);
    }

    public function destroy(Request $req, $id)
    {
        $user = Comment::where('is_deleted', 0)->find($id);
        if (!$user)  return ApiResponse::NotFound('User not found');
        $authUser = UserService::getAuthUser($req);
        $user->is_deleted = 1;
        $user->deleted_uid = $authUser->id;
        $user->save();
        return res_success('User deleted successfully', null);
    }
}
