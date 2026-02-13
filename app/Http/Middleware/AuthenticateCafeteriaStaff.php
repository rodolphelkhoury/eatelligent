<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCafeteriaStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('sanctum-cafeteria-staff')->check()) {
            return response()->json([
                'message' => 'Cafeteria staff not authenticated.',
            ], 401);
        }

        Auth::shouldUse('sanctum-cafeteria-staff');

        return $next($request);
    }
}
