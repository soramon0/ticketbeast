<?php

namespace Tests\Feature;

use App\Billing\FakePaymentGateway;
use App\Billing\PaymentGateway;
use App\Models\Concert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseTicketsTest extends TestCase
{
	use RefreshDatabase;

	function test_customer_can_purchase_concert_tickets()
	{
		$paymentGateway = new FakePaymentGateway;
		$this->app->instance(PaymentGateway::class, $paymentGateway);

		$concert = Concert::factory()->create(['ticket_price' => 3250]);

		$response = $this->json('POST', "/concerts/{$concert->id}/orders", [
			'email' => 'john@example.com',
			'ticket_quantity' => 3,
			'payment_token' => $paymentGateway->getValidTestToken(),
		]);

		$response->assertStatus(201);

		$this->assertEquals(9750, $paymentGateway->totalCharges());

		$order = $concert->orders()->where('email', 'john@example.com')->first();
		$this->assertNotNull($order);
		$this->assertEquals(3, $order->tickets()->count());
	}
}
