<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return res_fail('You need to login first.', [], 1, 403);
        }
        if (intval($user->role_id) != Role::SYSTEM_ADMIN) {
            return res_fail('You are not admin.', [], 1, 403);
        }
        return $next($request);
    }
}
