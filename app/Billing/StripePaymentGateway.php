<?php

namespace App\Billing;

use App\Billing\PaymentGateway;
use Stripe\Charge;
use Stripe\Exception\InvalidRequestException;
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
        try {
            $this->stripe->charges->create(
                [
                    'amount' => $amount,
                    'source' => $token,
                    'currency' => 'usd',
                ],
                ['api_key' => $this->apiKey]
            );
        } catch (InvalidRequestException $e) {
            throw new PaymentFailedException();
        }
    }

    public function getValidTestToken()
    {
        return $this->stripe->tokens->create([
            'card' => [
                'number' => '4242424242424242',
                'exp_month' => 1,
                'exp_year' => date('Y') + 1,
                'cvc' => '123',
            ],
        ], ['api_key' => $this->apiKey])->id;
    }

    public function newChargesDuring($callback)
    {
        $latestCharge = $this->lastCharge();
        $callback($this);
        return $this->newChargesSince($latestCharge)->pluck('amount');
    }

    private function lastCharge(): Charge
    {
        return Charge::all(
            ['limit' => 1],
            ['api_key' => $this->apiKey]
        )['data'][0];
    }

    private function newChargesSince(Charge $charge = null)
    {
        $newCharges = Charge::all(
            ['ending_before' => $charge ? $charge->id : null],
            ['api_key' => $this->apiKey]
        )['data'];

        return collect($newCharges);
    }
}
