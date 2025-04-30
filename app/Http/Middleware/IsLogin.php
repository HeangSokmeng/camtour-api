<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return res_fail('You need to login first.', [], 400, 403);
        }
        return $next($request);
    }
}
