<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('sanctum-user')->check()) {
            return response()->json([
                'message' => 'User not authenticated.',
            ], 401);
        }

        Auth::shouldUse('sanctum-user');

        return $next($request);
    }
}
