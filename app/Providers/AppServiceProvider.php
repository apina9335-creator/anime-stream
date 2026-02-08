<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <--- 1. WAJIB ADA INI BIAR GAK ERROR

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
        // <--- 2. LOGIKA PAKSA HTTPS (KHUSUS RAILWAY)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}