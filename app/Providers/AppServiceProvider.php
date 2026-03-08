<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

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
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('room-create', function (Request $request) {
            return Limit::perMinute(5)->by(\Illuminate\Support\Facades\Auth::guard('admin')->id() ?: $request->ip());
        });

        RateLimiter::for('proxy', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });
    }
}
