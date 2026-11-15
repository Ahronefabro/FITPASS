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

        // Share the unread notification count with the staff layout (red badge).
        View::composer('staff.layout', function ($view) {
            $view->with('unreadCount', Auth::check()
                ? StaffNotification::unread()->count()
                : 0);
        });
    }
}