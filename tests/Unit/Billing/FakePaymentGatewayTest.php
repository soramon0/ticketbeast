<?php

namespace Tests\Unit;

use App\Billing\FakePaymentGateway;
use App\Billing\PaymentFailedException;
use Tests\TestCase;

class FakePaymentGatewayTest extends TestCase
{

	public function test_charges_with_a_valid_payment_token_are_successful()
	{
		$paymentGateway = new FakePaymentGateway;

		$paymentGateway->charge(2500, $paymentGateway->getValidTestToken());

		$this->assertEquals(2500, $paymentGateway->totalCharges());
	}

	public function test_charges_with_an_invalid_payment_token_fail()
	{
		try {
			$paymentGateway = new FakePaymentGateway;
			$paymentGateway->charge(2500, 'invalid-payment-token');
		} catch (PaymentFailedException $e) {
			$this->assertEquals(0, $paymentGateway->totalCharges());
			return;
		}

		$this->fail();
	}

	public function test_running_a_hook_before_the_first_charge()
	{
		$paymentGateway = new FakePaymentGateway;
		$callbackRan = false;

		$paymentGateway->beforeFirstCharge(function ($paymentGateway) use (&$callbackRan) {
			$callbackRan = true;
			$this->assertEquals(0, $paymentGateway->totalCharges());
		});

		$paymentGateway->charge(2500, $paymentGateway->getValidTestToken());
		$this->assertTrue($callbackRan);
		$this->assertEquals(2500, $paymentGateway->totalCharges());
	}
}
