<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Mendaftarkan alias middleware 'admin'
        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
        ]);
    }) // <-- Perbaikan ada di sini (hapus penutup ganda)
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();