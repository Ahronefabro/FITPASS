<?php

namespace App\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Throwable;

/**
 * The two portals and which account roles each one admits.
 *
 * There is one sign-in page. The role on the user record decides which portal
 * it opens, and the portal middleware keeps checking it on every request after
 * that, so a role changed in the staff screens takes effect straight away.
 */
final class Portal
{
    public const OWNER = 'owner';

    public const STAFF = 'staff';

    /**
     * Roles listed under more than one portal, like Owner and Admin, sign in to
     * the first one here and may still open the others.
     */
    public const ROLES = [
        self::OWNER => ['Owner', 'Admin'],
        self::STAFF => ['Staff', 'Admin', 'Owner', 'Front Desk', 'Trainers'],
    ];

    /** @return list<string> */
    public static function all(): array
    {
        return [self::OWNER, self::STAFF];
    }

    public static function allows(string $portal, ?string $role): bool
    {
        if ($role === null || $role === '') {
            return false;
        }

        return in_array($role, self::ROLES[$portal] ?? [], true);
    }

    /** The portal a role signs in to, or null when it belongs to neither. */
    public static function forRole(?string $role): ?string
    {
        foreach (self::all() as $portal) {
            if (self::allows($portal, $role)) {
                return $portal;
            }
        }

        return null;
    }

    public static function homeRoute(?string $portal): string
    {
        return $portal === self::STAFF ? 'staff.dashboard' : 'overview';
    }

    /**
     * Which portal a url within this app belongs to, read from the portal
     * middleware on its route. The url itself is no guide: the owner's own
     * staff directory sits at /staff, right inside the staff portal's prefix.
     */
    public static function forUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        try {
            $route = app('router')->getRoutes()->match(Request::create($path));
        } catch (Throwable) {
            return null;
        }

        return self::forRoute($route);
    }

    private static function forRoute(?Route $route): ?string
    {
        foreach ($route?->gatherMiddleware() ?? [] as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            foreach (self::all() as $portal) {
                if ($middleware === 'portal:'.$portal) {
                    return $portal;
                }
            }
        }

        return null;
    }
}
