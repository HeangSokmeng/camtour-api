<?php

namespace App\Services;
use App\Models\User;
use DataResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserService
{
    // Your service methods go here
    public static function getAuthUser($action=''){
        $user = JWTAuth::user();
        if($user){
            $hasUser = User::where('id',$user->id)->first();
            if($hasUser){
                return DataResponse::JsonRaw([
                    'error'=>false,
                    'status_code' => 200,
                    'status' => 'OK',
                    'id' => $hasUser->id,
                    'user_name' => $hasUser->first_name  . ' ' . $hasUser->last_name,
                    'info'=>$hasUser
                ]);
            }
        }
        return DataResponse::Unauthorized();
    }
}
