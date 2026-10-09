<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(5)
                ->by(strtolower($request->input('email')) . '|' . $request->ip());
        });

        RateLimiter::for('register', function ($request) {
            return Limit::perMinute(3)
                ->by($request->ip());
        });

        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(120)
                ->by(
                    $request->user()?->id
                    ?? $request->ip()
                );
        });
    }
}