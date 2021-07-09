<?php

namespace Tests\Unit;

use App\Billing\FakePaymentGateway;
use Tests\TestCase;

class FakePaymentGatewayTest extends TestCase
{

	public function test_charges_with_a_valid_payment_token_are_successful()
	{
		$paymentGateway = new FakePaymentGateway;

		$paymentGateway->charge(2500, $paymentGateway->getValidTestToken());

		$this->assertEquals(2500, $paymentGateway->totalCharges());
	}
}
