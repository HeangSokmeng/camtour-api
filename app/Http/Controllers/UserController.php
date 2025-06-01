<?php

namespace App\Http\Controllers;

use ApiResponse;
use App\Http\Resources\UserDetailResource;
use App\Http\Resources\UserIndexResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
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
            'image' => 'nullable|file|mimetypes:image/png,image/jpeg|max:2048',
            'phone' => 'nullable|string|max:250',
            'email' => 'required|email|max:250|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'is_lock' => 'nullable'
        ]);
        $image = User::DEFAULT_IMAGE;
        if ($req->hasFile('image')) {
            $path = $req->file('image')->store('users', ['disk' => 'public']);
            $image = basename($path);
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
            $user->role_id = 3;
        }
        $user->save();
        if ($req->has('roles')) {
            $user->roles()->sync($req->roles);
        } else if ($req->role_id) {
            $user->roles()->sync([$req->role_id]);
        } else {
            $user->roles()->sync([3]);
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
        $user = UserService::getAuthUser($req);
        $users = User::with(['roles'])->where('is_deleted', 0);
        if ($user->role_id == 2) {
            $users->where('role_id', 3);
        }
        else {
            // Add a fallback if needed
            $users->whereIn('role_id', [2, 3]);
        }

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

    public function lockUser(Request $req)
    {
        $id = $req->id ?? 0;
        $userAuth = UserService::getAuthUser($req);
        if ($id == $userAuth->id) {
            return res_fail('Can not lock youself.');
        }
        $user = User::where('is_deleted', 0)->find($id);
        if (!$user)  return ApiResponse::NotFound('user not found');
        $authUser = UserService::getAuthUser($req);
        if ($user->id === $authUser->id)  return ApiResponse::ValidateFail('You cannot lock your own account');
        $user->is_lock = $user->is_lock === 'lock' ? 'unlock' : 'lock';
        $user->save();
        $message = $user->is_lock === 'lock' ? 'User has been locked' : 'User has been unlocked';
        return res_success($message);
    }

    public function update(Request $req, $id)
    {
        $req->validate([
            'first_name' => 'required|string|max:250',
            'last_name' => 'required|string|max:250',
            'gender' => 'nullable|integer|in:1,2',
            'role_id' => 'nullable|integer|min:1|exists:roles,id',
            'roles' => 'nullable|array',
            'roles.*' => 'integer|exists:roles,id',
            'image' => 'nullable|file|mimetypes:image/png,image/jpeg|max:2048',
            'phone' => 'nullable|string|max:250',
            'email' => 'nullable|email|max:250|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'is_lock' => 'nullable'
        ]);
        $user = User::findOrFail($id);
        // Handle image update
        if ($req->hasFile('image')) {
            $path = $req->file('image')->store('users', ['disk' => 'public']);
            $user->image = basename($path); // Only store the filename
        }
        // Update other fields
        $user->fill($req->only([
            'first_name',
            'last_name',
            'gender',
            'role_id',
            'phone',
            'email',
            'is_lock'
        ]));
        // Handle password if provided
        if ($req->filled('password')) {
            $user->password = Hash::make($req->password);
        }
        // Set default role_id if not set
        if (!$user->role_id) {
            $user->role_id = 3;
        }
        $user->save();
        // Handle roles relationship
        if ($req->has('roles')) {
            $user->roles()->sync($req->roles);
        } else if ($req->role_id) {
            $user->roles()->sync([$req->role_id]);
        } else {
            $user->roles()->sync([3]);
        }
        $user->load('roles');
        return res_success('Update user successful');
    }


    public function destroy(Request $req, $id)
    {
        $user = User::where('is_deleted', 0)->find($id);
        if (!$user)  return ApiResponse::NotFound('User not found');
        $authUser = UserService::getAuthUser($req);
        if ($user->id === $authUser->id)  return ApiResponse::ValidateFail('You cannot delete your own account');
        $user->is_deleted = 1;
        $user->deleted_uid = $authUser->id;
        $user->save();
        return res_success('User deleted successfully', null);
    }
}
