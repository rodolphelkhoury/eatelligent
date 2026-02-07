<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('sanctum-admin')->check()) {
            return response()->json([
                'message' => 'Admin not authenticated.',
            ], 401);
        }

        Auth::shouldUse('sanctum-admin');

        return $next($request);
    }
}
