<?php

namespace App\Http\Middleware;

use App\Auth\Portal;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps each portal to the roles it admits. One session serves both portals, so
 * this is what separates them: the role is re-read on every request, and a role
 * changed or removed takes effect on the next page rather than the next sign-in.
 */
class EnsurePortalAccess
{
    public function handle(Request $request, Closure $next, string $portal): Response
    {
        $user = $request->user();

        // The auth middleware runs ahead of this, so a missing user means the
        // route was wired up wrong. Refuse rather than wave it through.
        if ($user === null) {
            abort(403, 'This page is not reachable without signing in.');
        }

        if (Portal::allows($portal, $user->role)) {
            return $next($request);
        }

        // The scanner screens talk to the staff portal over json and would read
        // a redirect to a page as a successful scan.
        if ($request->expectsJson()) {
            abort(403, 'This account does not have '.$portal.' access.');
        }

        // Send people to the portal their role does admit them to, rather than
        // to a dead end.
        if ($theirs = Portal::forRole($user->role)) {
            return redirect()->route(Portal::homeRoute($theirs));
        }

        // The role was changed or taken away after this session started.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withErrors(['username' => 'This account no longer has access.']);
    }
}
