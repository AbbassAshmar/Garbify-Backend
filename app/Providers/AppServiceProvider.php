<?php

namespace App\Providers;

use App\Http\Controllers\StripeController;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services. register s. are added to the service container and 
     * can be used anywhere in the app by calling App::make() 
     * instead of instantiating instances manually
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
