<?php

namespace App\Http\Controllers\Web;

use ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserDetailResource;
use App\Http\Resources\UserIndexResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    public function store(Request $req)
    {
        $req->validate([
            'first_name' => 'required|string|max:250',
            'last_name' => 'required|string|max:250',
            'gender' => 'nullable|integer|in:1,2',
            'role_id' => 'nullable|integer|min:1|exists:roles,id',
            'roles' => 'nullable|array',
            'roles.*' => 'integer|exists:roles,id',  // Add validation for roles array
            'image' => 'required|file|mimetypes:image/png,image/jpeg|max:2048',
            'phone' => 'nullable|string|max:250',
            'email' => 'required|email|max:250|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'is_lock' => 'nullable'
        ]);
        $image = User::DEFAULT_IMAGE;
        if ($req->hasFile('image')) {
            $image = $req->file('image')->store('users', ['disk' => 'public']);
        }
        $user = new User($req->only([
            'first_name',
            'last_name',
            'gender',
            'role_id',
            'phone',
            'email',
            'is_lock'
        ]));
        $user->image = $image;
        $user->password = Hash::make($req->password);
        if (!$req->role_id) {
            $user->role_id = 4;
        }
        $user->save();
        if ($req->has('roles')) {
            $user->roles()->sync($req->roles);
        } else if ($req->role_id) {
            $user->roles()->sync([$req->role_id]);
        } else {
            $user->roles()->sync([4]);
        }
        $user->load('roles');

        return res_success('Store new user successful', new UserDetailResource($user));
    }
    public function index(Request $req)
    {
        $req->validate([
            'search' => 'nullable|string|max:50',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'role_id' => 'nullable|integer|exists:roles,id',
            'gender' => 'nullable|integer|in:1,2',
        ]);
        $perPage = $req->filled('per_page') ? intval($req->input('per_page')) : 15;
        $users = User::with(['roles'])->where('is_deleted', 0)->where('role_id', 4);
        if ($req->filled('search')) {
            $s = $req->input('search');
            $users->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%$s%")
                    ->orWhere('last_name', 'like', "%$s%")
                    ->orWhere('email', 'like', "%$s%")
                    ->orWhere('phone', 'like', "%$s%");
            });
        }
        if ($req->filled('role_id')) {
            $users->whereHas('roles', function ($q) use ($req) {
                $q->where('roles.id', $req->input('role_id'));
            });
        }
        if ($req->filled('gender')) {
            $users->where('gender', $req->input('gender'));
        }
        $users = $users->orderByDesc('id')->paginate($perPage);
        return res_paginate($users, "Get all users success", UserIndexResource::collection($users));
    }

    public function theirInfo(Request $req)
    {
        $user = UserService::getAuthUser($req);
        $users = User::with(['roles'])->where('is_deleted', 0)->where('role_id', 4)->where('id', $user->id)->get();
        if (!$user) return ApiResponse::Unauthorized();
        if ($req->filled('role_id')) {
            $users->whereHas('roles', function ($q) use ($req) {
                $q->where('roles.id', $req->input('role_id'));
            });
        }
        if ($req->filled('gender')) {
            $users->where('gender', $req->input('gender'));
        }
        return res_success("Get Information success", UserIndexResource::collection($users));
    }

    public function lockUser(Request $req)
    {
        $id = $req->id ?? 0;
        $user = User::where('is_deleted', 0)->find($id);
        if (!$user)  return ApiResponse::NotFound('user not found');
        $authUser = UserService::getAuthUser($req);
        if ($user->id === $authUser->id)  return ApiResponse::ValidateFail('You cannot lock your own account');
        $user->is_lock = $user->is_lock === 'lock' ? 'unlock' : 'lock';
        $user->save();
        $message = $user->is_lock === 'lock' ? 'User has been locked' : 'User has been unlocked';
        return ApiResponse::JsonResult(null, $message);
    }

    public function update(Request $req)
    {
        $userService = UserService::getAuthUser($req);
        $user = User::where('is_deleted', 0)->where('id', $userService->id)->first();
        if (!$user)  return ApiResponse::Unauthorized();
        $req->validate([
            'first_name' => 'nullable|string|max:250',
            'last_name' => 'required|string|max:250',
            'gender' => 'nullable|integer|in:1,2',
            // 'roles' => 'nullable|array',
            // 'roles.*' => 'integer|exists:roles,id',
            'image' => 'nullable|file|mimetypes:image/png,image/jpeg|max:2048',
            'phone' => 'nullable|string|max:250',
            'email' => 'nullable|email|max:250|unique:users,email,' . $userService->id,
            'password' => 'nullable|string|min:8|confirmed',
            'is_lock' => 'nullable'
        ]);
        // if ($req->hasFile('image')) {
        //     if ($user->image && $user->image !== User::DEFAULT_IMAGE)  Storage::disk('public')->delete($user->image);
        //     $user->image = $req->file('image')->store('users', ['disk' => 'public']);
        // }

        if ($req->hasFile('image')) {
            $path = $req->file('image')->store('users', ['disk' => 'public']);
            $user->image = basename($path); // Only store the filename
        }
        $user->fill($req->only([
            'first_name',
            'last_name',
            'gender',
            'phone',
            'email',
            'is_lock'
        ]));
        if ($req->filled('password'))   $user->password = Hash::make($req->password);
        $user->update_uid = $userService->id;
        $user->save();
        return res_success('User updated successfully', new UserDetailResource($user));
    }

    public function destroy(Request $req)
    {
        $userService = UserService::getAuthUser($req);
        $user = User::where('is_deleted', 0)->where('id', $userService->id)->first();
        if (!$user)  return ApiResponse::NotFound('User not found');
        $authUser = UserService::getAuthUser($req);
        if ($user->id === $authUser->id)  return ApiResponse::ValidateFail('You cannot delete your own account');
        $user->is_deleted = 1;
        $user->deleted_uid = $authUser->id;
        $user->save();
        return res_success('User deleted successfully', null);
    }
}
