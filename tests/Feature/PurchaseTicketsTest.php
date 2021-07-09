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

	protected function setUp(): void
	{
		parent::setUp();

		$this->paymentGateway = new FakePaymentGateway;
		$this->app->instance(PaymentGateway::class, $this->paymentGateway);
	}

	function orderTickets($concert, $params)
	{
		return $this->json('POST', "/concerts/{$concert->id}/orders", $params);
	}

	function assertValidationError($response, $field)
	{
		$response->assertStatus(422);
		$this->assertArrayHasKey($field, $response->json()['errors']);
	}

	function test_customer_can_purchase_concert_tickets()
	{
		$concert = Concert::factory()->create(['ticket_price' => 3250]);

		$response = $this->orderTickets($concert, [
			'email' => 'john@example.com',
			'ticket_quantity' => 3,
			'payment_token' => $this->paymentGateway->getValidTestToken(),
		]);

		$response->assertStatus(201);

		$this->assertEquals(9750, $this->paymentGateway->totalCharges());

		$order = $concert->orders()->where('email', 'john@example.com')->first();
		$this->assertNotNull($order);
		$this->assertEquals(3, $order->tickets()->count());
	}

	function test_email_is_required_to_purchase_tickets()
	{
		$concert = Concert::factory()->create();

		$response = $this->orderTickets($concert, [
			'ticket_quantity' => 3,
			'payment_token' => $this->paymentGateway->getValidTestToken(),
		]);

		$this->assertValidationError($response, 'email');
	}

	function test_email_must_be_valid_to_purchase_tickets()
	{
		$concert = Concert::factory()->create();

		$response = $this->orderTickets($concert, [
			'email' => 'not-an-email-address',
			'ticket_quantity' => 3,
			'payment_token' => $this->paymentGateway->getValidTestToken(),
		]);

		$this->assertValidationError($response, 'email');
	}

	function test_ticket_quantity_is_required_to_purchase_tickets()
	{
		$concert = Concert::factory()->create();

		$response = $this->orderTickets($concert, [
			'email' => 'jane@example.com',
			'payment_token' => $this->paymentGateway->getValidTestToken(),
		]);

		$this->assertValidationError($response, 'ticket_quantity');
	}

	function test_ticket_quantity_must_be_at_least_1_to_purchase_tickets()
	{
		$concert = Concert::factory()->create();

		$response = $this->orderTickets($concert, [
			'email' => 'jane@example.com',
			'ticket_quantity' => 0,
			'payment_token' => $this->paymentGateway->getValidTestToken(),
		]);

		$this->assertValidationError($response, 'ticket_quantity');
	}

	function test_payment_token_is_required()
	{
		$concert = Concert::factory()->create();

		$response = $this->orderTickets($concert, [
			'email' => 'jane@example.com',
			'ticket_quantity' => 3,
		]);

		$this->assertValidationError($response, 'payment_token');
	}
}
