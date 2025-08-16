<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request; // ← penting

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Untuk request API/JSON, JANGAN redirect — balas 401 saja
        $middleware->redirectGuestsTo(function (Request $request) {
            return $request->expectsJson() ? null : route('login');
            // Atau kalau pure API (tanpa halaman login sama sekali):
            // return null;
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
