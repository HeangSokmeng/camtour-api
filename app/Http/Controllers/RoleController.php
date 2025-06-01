<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\UserService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $req)
    {
        $req->validate([
            'search' => 'nullable|string|max:50',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'role_id' => 'nullable|integer|exists:roles,id',
            'gender' => 'nullable|integer|in:1,2',
        ]);
        $roles = Role::get();
        return res_success("Get all users success", $roles);
    }
    public function roleWeb(Request $req)
    {
        $user = UserService::getAuthUser($req);
        $req->validate([
            'search' => 'nullable|string|max:50',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'role_id' => 'nullable|integer|exists:roles,id',
            'gender' => 'nullable|integer|in:1,2',
        ]);
        $role = Role::where('is_deleted',0);
        if ($user->role_id == 2) {
            $role->where('id', 3);
        } else {
            $role->whereIn('id', [2, 3]);
        }
        $roles = $role->get();
        return res_success("Get all users success", $roles);
    }
}
