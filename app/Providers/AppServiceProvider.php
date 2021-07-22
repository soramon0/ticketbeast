<?php

namespace App\Providers;

use App\Billing\PaymentGateway;
use App\Billing\StripePaymentGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(StripePaymentGateway::class, function () {
            return new StripePaymentGateway();
        });

        $this->app->bind(PaymentGateway::class, function () {
            return new StripePaymentGateway();
        });
    }
}
