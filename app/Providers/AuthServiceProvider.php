<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Settings\FeatureFlag::class => \App\Policies\FeatureFlagPolicy::class,
        // TODO: map additional models to policies as they are introduced
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Example global gates (optional):
        Gate::define('admin', function ($user) {
            return (bool) ($user->is_admin ?? false);
        });
    }
}
