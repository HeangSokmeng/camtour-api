<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user('sanctum');
        Log::info($user);

        if (!$user) {
            return res_fail('You need to login first.', [], 1, 403);
        }

        // Convert roles to integer values if they're constants
        $allowedRoles = array_map(function($role) {
            return constant('App\Models\Role::' . strtoupper($role));
        }, $roles);

        // Check if user has any of the allowed roles
        if (!in_array(intval($user->role_id), $allowedRoles)) {
            return res_fail('You do not have permission to access this resource.', [], 1, 403);
        }

        return $next($request);
    }
}
