<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        if (!file_exists(storage_path('installed'))) {
            config([
                'session.driver' => 'file',
                'cache.default' => 'file',
            ]);
        }
    }


    public function boot(): void
    {
        \App\Models\Notification::observe(\App\Observers\NotificationObserver::class);
        Schema::defaultStringLength(191);

        RateLimiter::for('global_api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('strict_action', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('public_api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
