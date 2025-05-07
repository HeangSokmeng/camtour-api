<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateInfoRequest;
use App\Http\Resources\LoginResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function updatePass(Request $req): JsonResponse
    {
        // validation
        $req->validate([
            'current_password' => 'required|string|max:250',
            'new_password' => 'required|string|max:250|confirmed',
            'new_password_confirmation' => 'required|string|max:250'
        ]);

        // check current password
        $loginUser = $req->user('sanctum');
        $user = User::where('id', $loginUser->id)->with('roles')->first();
        if (!Hash::check($req->input('current_password'), $user->password)) return res_fail('Incorrect current password.');
        // update password
        $user->password = $req->input('new_password');
        $user->save();
        return res_success('Update password successful.', new LoginResource($user));
    }

    public function updateInfo(Request $req): JsonResponse
    {
        // validation
        $loginUser = $req->user('sanctum');
        $req->validate([
            'first_name' => 'nullable|string|max:250',
            'last_name' => 'nullable|string|max:250',
            'gender' => 'nullable|integer|min:0|max:2',
            'phone' => 'nullable|string|max:250',
            'email' => "nullable|email|max:250|unique:users,email,$loginUser->id",
            'image' => 'nullable|image|mimetypes:image/png,image/jpeg|max:2048',
        ]);

        // update data & response
        $user = User::where('id', $loginUser->id)->with('roles')->first();
        if ($req->filled('first_name')) {
            $user->first_name = $req->input('first_name');
        }
        if ($req->has('last_name')) {
            $user->last_name = $req->input('last_name');
        }
        if ($req->filled('gender')) {
            $gender = intval($req->input('gender'));
            if ($gender == 0) {
                $user->gender = null;
            } else {
                $user->gender = $gender;
            }
        }
        if ($req->has('phone')) {
            $user->phone = $req->input('phone');
        }
        if ($req->filled('email')) {
            $user->email = $req->input('email');
        }
        if ($req->hasFile('image')) {
            $image = $req->file('image');
            $image->store('avatars', ['disk' => 'public']);
            if ($user->image != User::DEFAULT_IMAGE) {
                Storage::disk('public')->delete('avatars/' . $user->image);
            }
            $user->image = $image->hashName();
        }
        $user->save();
        return res_success('Update profile information successful.', new LoginResource($user));
    }

    public function resetImage(Request $req): JsonResponse
    {
        // reset image & response
        $loginUser = $req->user('sanctum');
        $user = User::where('id', $loginUser->id)->with('roles')->first();
        if ($user->image != User::DEFAULT_IMAGE) {
            Storage::disk('public')->delete('avatars/' . $user->image);
        }
        $user->image = User::DEFAULT_IMAGE;
        $user->save();
        return res_success('Reset profile image successful.', new LoginResource($user));
    }
}
