<?php

namespace Tests\Unit\Billing;

use App\Billing\PaymentFailedException;
use App\Billing\PaymentGateway;

trait PaymentGatewayContractTests
{
	abstract protected function getPaymentGateway(): PaymentGateway;

	public function test_can_fetch_charges_created_during_a_callback()
	{
		$paymentGateway = $this->getPaymentGateway();
		$paymentGateway->charge(2000, $paymentGateway->getValidTestToken());
		$paymentGateway->charge(3000, $paymentGateway->getValidTestToken());

		$newCharges = $paymentGateway->newChargesDuring(function ($paymentGateway) {
			$paymentGateway->charge(4000, $paymentGateway->getValidTestToken());
			$paymentGateway->charge(5000, $paymentGateway->getValidTestToken());
		});

		$this->assertCount(2, $newCharges);
		$this->assertEquals([5000, 4000], $newCharges->all());
	}

	public function test_charges_with_a_valid_payment_token_are_successful()
	{
		$paymentGateway = $this->getPaymentGateway();

		$newCharges = $paymentGateway->newChargesDuring(function ($paymentGateway) {
			$paymentGateway->charge(2500, $paymentGateway->getValidTestToken());
		});

		$this->assertCount(1, $newCharges);
		$this->assertEquals(2500, $newCharges->sum());
	}

	public function test_charges_with_an_invalid_payment_token_fail()
	{
		$paymentGateway = $this->getPaymentGateway();

		$newCharges = $paymentGateway->newChargesDuring(function ($paymentGateway) {
			try {
				$paymentGateway->charge(2500, 'invalid-payment-token');
			} catch (PaymentFailedException $e) {
				return;
			}

			$this->fail('Charging with an invalid payment token did not throw a PaymentFailedException.');
		});

		$this->assertCount(0, $newCharges);
	}
}
