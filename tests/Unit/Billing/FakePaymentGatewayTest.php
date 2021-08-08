<?php

namespace Tests\Unit\Billing;

use App\Billing\FakePaymentGateway;
use Tests\Unit\Billing\PaymentGatewayContractTests;
use Tests\TestCase;

class FakePaymentGatewayTest extends TestCase
{
	use PaymentGatewayContractTests;

	protected function getPaymentGateway(): FakePaymentGateway
	{
		return new FakePaymentGateway();
	}

	public function test_running_a_hook_before_the_first_charge()
	{
		$paymentGateway = new FakePaymentGateway;
		$timesCallbackRan = 0;

		$paymentGateway->beforeFirstCharge(function ($paymentGateway) use (&$timesCallbackRan) {
			$timesCallbackRan++;
			$paymentGateway->charge(2500, $paymentGateway->getValidTestToken());
			$this->assertEquals(2500, $paymentGateway->totalCharges());
		});

		$paymentGateway->charge(2500, $paymentGateway->getValidTestToken());
		$this->assertEquals(1, $timesCallbackRan);
		$this->assertEquals(5000, $paymentGateway->totalCharges());
	}
}
