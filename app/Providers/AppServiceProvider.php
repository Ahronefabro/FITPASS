<?php

namespace App\Providers;

use App\Models\Member;
use App\Models\PersonalAccessToken;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // App members reset their password before staff confirm them too.
        Auth::provider('members', fn ($app) => (new EloquentUserProvider($app['hash'], Member::class))
            ->withQuery(fn ($query) => $query->withoutGlobalScope('approved')));
    }
}
