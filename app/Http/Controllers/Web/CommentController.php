<?php

namespace App\Http\Controllers\Web;

use ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Services\UserService;
use Illuminate\Http\Request;

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
            'comment'
        ]));
        $comment->photos = $imagePaths;
        if(!$req->location_id) return ApiResponse::NotFound('Location not fount');
        $comment->status = $req->has('status') ? $req->status : true;
        $user = UserService::getAuthUser($req);
        $comment->create_uid = $user->id;
        $comment->update_uid = $user->id;
        $comment->customer_id = $user->id;
        $comment->save();
        return res_success('success created.');
    }

    public function getAllComment(Request $req){
        $comments = Comment::query()->selectRaw('id,customer_id,location_id,comment,photos')->get();
        return res_success("Get all commaent", $comments);
    }
}
