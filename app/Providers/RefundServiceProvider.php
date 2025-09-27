<?php

namespace App\Providers;

use App\Services\RefundService;
use Illuminate\Support\ServiceProvider;

class RefundServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(RefundService::class, function ($app) {
            return new RefundService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
