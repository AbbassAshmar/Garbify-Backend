<?php

namespace App\Providers;

use App\Http\Controllers\StripeController;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(StripeController::class, function(){
            $stripe = new StripeClient(env("STRIPE_SECRET"));
            return new StripeController($stripe);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
