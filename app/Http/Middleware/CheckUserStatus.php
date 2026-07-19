<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->status !== UserStatus::Active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not active. Please contact administrator.',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
