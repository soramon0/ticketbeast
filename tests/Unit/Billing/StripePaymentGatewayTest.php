<?php

namespace Tests\Unit;

use App\Billing\StripePaymentGateway;
use Stripe\Charge;
use Stripe\Token;
use Tests\TestCase;

class StripePaymentGatewayTest extends TestCase
{
	private Charge $_lastCharge;

	protected function setUp(): void
	{
		parent::setUp();

		$this->_lastCharge = $this->lastCharge();
	}

	private function lastCharge(): Charge
	{
		return Charge::all(
			['limit' => 1],
			['api_key' => config('services.stripe.key')]
		)['data'][0];
	}

	private function newCharges()
	{
		return Charge::all(
			['limit' => 1, 'ending_before' => $this->_lastCharge],
			['api_key' => config('services.stripe.key')]
		)['data'];
	}

	private function validToken()
	{
		return Token::create([
			'card' => [
				'number' => '4242424242424242',
				'exp_month' => 1,
				'exp_year' => date('Y') + 1,
				'cvc' => '123',
			],
		], ['api_key' => config('services.stripe.key')])->id;
	}

	public function test_charges_with_a_valid_payment_token_are_successful()
	{
		$paymentGateway = new StripePaymentGateway();

		$paymentGateway->charge(2500, $this->validToken());

		$this->assertCount(1, $this->newCharges());
		$this->assertEquals(2500, $this->lastCharge()->amount);
	}
}
