<?php

namespace App\Billing;

use App\Billing\PaymentGateway;
use Stripe\StripeClient;

class StripePaymentGateway implements PaymentGateway
{
    private String $apiKey;
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
        $this->apiKey = config('services.stripe.key');
    }

    public function charge($amount, $token)
    {
        $this->stripe->charges->create(
            [
                'amount' => $amount,
                'source' => $token,
                'currency' => 'usd',
            ],
            ['api_key' => $this->apiKey]
        );
    }
}
