<?php

namespace Tests\Unit\Billing;

use App\Billing\StripePaymentGateway;
use Tests\TestCase;

/**
 * @group integration
 */
class StripePaymentGatewayTest extends TestCase
{
	use PaymentGatewayContractTests;

	protected function getPaymentGateway(): StripePaymentGateway
	{
		return new StripePaymentGateway();
	}
}
