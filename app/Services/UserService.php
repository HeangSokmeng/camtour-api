<?php

namespace App\Services;

use App\Models\User;
use DataResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserService
{
    public static function getAuthUser(Request $request,$action = '')
    {
        $user = $request->user('sanctum');
        if ($user) {
            $hasUser = User::find($user->id);
            if ($hasUser) {
                return DataResponse::JsonRaw([
                    'error' => false,
                    'status_code' => 200,
                    'status' => 'OK',
                    'id' => $hasUser->id,
                    'user_name' => $hasUser->first_name . ' ' . $hasUser->last_name,
                    'first_name' => $hasUser->first_name,
                    'last_name' => $hasUser->last_name,
                    'email' => $hasUser->email,
                    'phone' => $hasUser->phone,
                    'role_id' => $hasUser->role_id,
                    'info' => $hasUser,
                ]);
            }
        }
        Log::warning("Unauthenticated request to getAuthUser");
        return DataResponse::Unauthorized();
    }

}
