<?php

namespace App\Providers;

use App\Models\Member;
use App\Models\PersonalAccessToken;
use App\Models\StaffNotification;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // App members reset their password before staff confirm them too.
        Auth::provider('members', fn ($app) => (new EloquentUserProvider($app['hash'], Member::class))
            ->withQuery(fn ($query) => $query->withoutGlobalScope('approved')));

        // Share the red badge counts with the staff layout: unread notifications,
        // and app sign-ups still waiting for staff to confirm the payment.
        View::composer('staff.layout', function ($view) {
            $view->with('unreadCount', Auth::check() ? StaffNotification::unread()->count() : 0);
            $view->with('pendingCount', Auth::check() ? Member::pending()->count() : 0);
        });
    }
}