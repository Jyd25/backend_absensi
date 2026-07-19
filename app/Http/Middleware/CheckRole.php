<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !$user->role) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. No role assigned.',
                'data' => null,
            ], 403);
        }

        if (!in_array($user->role->name, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have the required role.',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
