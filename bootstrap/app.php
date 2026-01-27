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
    ->withMiddleware(function (Middleware $middleware): void {
        // Register alias middlewares
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'vendor' => \App\Http\Middleware\VendorMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
               'profile.complete' => \App\Http\Middleware\ProfileCompleteMiddleware::class, // Add this
 'session.check' => \App\Http\Middleware\CheckSessionExpiration::class, // ← ADD THIS
               ]);

        // Web middleware group (default Laravel 11 setup)
        $middleware->web(append: [
            // You can append custom middlewares to web group if needed
        ]);

        // API middleware group (default Laravel 11 setup)
        $middleware->api(append: [
            // You can append custom middlewares to api group if needed
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
