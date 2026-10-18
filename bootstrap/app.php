<?php

use App\Auth\Portal;
use App\Http\Middleware\EnsurePortalAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'portal' => EnsurePortalAccess::class,
        ]);

        // One sign-in page for everyone.
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Someone already signed in belongs on their own portal's home.
        $middleware->redirectUsersTo(
            fn (Request $request) => route(Portal::homeRoute(Portal::forRole($request->user()?->role)))
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
