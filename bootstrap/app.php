<?php

use App\Http\Middleware\AuthenticateJWT;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckUserStatus;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\LogRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: [__DIR__.'/../routes/auth.php', __DIR__.'/../routes/api.php'],
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt' => AuthenticateJWT::class,
            'role' => CheckRole::class,
            'status' => CheckUserStatus::class,
            'log.request' => LogRequest::class,
            'cors' => CorsMiddleware::class,
        ]);

        $middleware->api(prepend: [
            CorsMiddleware::class,
            LogRequest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
