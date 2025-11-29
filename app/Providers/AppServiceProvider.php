<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
    //     $this->registerPolicies();
    //     Auth::provider('users', function ($app, array $config) {
    //         return new \Illuminate\Auth\EloquentUserProvider($app['hash'], $config['model']);
    //     });
    }
}
