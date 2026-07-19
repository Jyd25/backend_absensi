<?php

namespace App\Http\Middleware;

use App\Models\ApiLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000);

        try {
            ApiLog::create([
                'user_id' => $request->user()?->id,
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => $response->getStatusCode(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_body' => $request->except(['password', 'password_confirmation', 'current_password', 'new_password']),
                'duration_ms' => $duration,
            ]);
        } catch (\Exception $e) {
            report($e);
        }

        return $response;
    }
}
