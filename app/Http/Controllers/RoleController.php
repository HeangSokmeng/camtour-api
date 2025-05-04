<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\Models\Role;
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
        return res_success("Get all users success",$roles);
    }
}
