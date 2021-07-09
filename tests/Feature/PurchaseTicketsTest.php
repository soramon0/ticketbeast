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

	function test_customer_can_purchase_tickets_to_published_concert()
	{
		$concert = Concert::factory()->published()->create(['ticket_price' => 3250]);
		$concert->addTickets(3);

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

	function test_customer_cannot_purchase_tickets_to_an_unpublished_concert()
	{
		$concert = Concert::factory()->unpublished()->create();

		$response = $this->orderTickets($concert, [
			'email' => 'john@example.com',
			'ticket_quantity' => 3,
			'payment_token' => $this->paymentGateway->getValidTestToken(),
		]);

		$response->assertStatus(404);
		$this->assertEquals(0, $concert->orders()->count());
		$this->assertEquals(0, $this->paymentGateway->totalCharges());
	}

	function test_an_order_is_not_created_if_payment_fails()
	{
		$concert = Concert::factory()->published()->create(['ticket_price' => 3250]);

		$response = $this->orderTickets($concert, [
			'email' => 'john@example.com',
			'ticket_quantity' => 3,
			'payment_token' => 'invalid-payment-token',
		]);

		$response->assertStatus(422);
		$order = $concert->orders()->where('email', 'john@example.com')->first();
		$this->assertNull($order);
	}

	function test_cannot_purchase_more_tickets_than_remaining()
	{
		$concert = Concert::factory()->published()->create();
		$concert->addTickets(50);

		$response = $this->orderTickets($concert, [
			'email' => 'john@example.com',
			'ticket_quantity' => 51,
			'payment_token' => 'invalid-payment-token',
		]);

		$response->assertStatus(422);

		$order = $concert->orders()->where('email', 'john@example.com')->first();
		$this->assertNull($order);
		$this->assertEquals(0, $this->paymentGateway->totalCharges());
		$this->assertEquals(50, $concert->ticketsRemaining());
	}

	function test_email_is_required_to_purchase_tickets()
	{
		$concert = Concert::factory()->published()->create();

		$response = $this->orderTickets($concert, [
			'ticket_quantity' => 3,
			'payment_token' => $this->paymentGateway->getValidTestToken(),
		]);

		$this->assertValidationError($response, 'email');
	}

	function test_email_must_be_valid_to_purchase_tickets()
	{
		$concert = Concert::factory()->published()->create();

		$response = $this->orderTickets($concert, [
			'email' => 'not-an-email-address',
			'ticket_quantity' => 3,
			'payment_token' => $this->paymentGateway->getValidTestToken(),
		]);

		$this->assertValidationError($response, 'email');
	}

	function test_ticket_quantity_is_required_to_purchase_tickets()
	{
		$concert = Concert::factory()->published()->create();

		$response = $this->orderTickets($concert, [
			'email' => 'jane@example.com',
			'payment_token' => $this->paymentGateway->getValidTestToken(),
		]);

		$this->assertValidationError($response, 'ticket_quantity');
	}

	function test_ticket_quantity_must_be_at_least_1_to_purchase_tickets()
	{
		$concert = Concert::factory()->published()->create();

		$response = $this->orderTickets($concert, [
			'email' => 'jane@example.com',
			'ticket_quantity' => 0,
			'payment_token' => $this->paymentGateway->getValidTestToken(),
		]);

		$this->assertValidationError($response, 'ticket_quantity');
	}

	function test_payment_token_is_required()
	{
		$concert = Concert::factory()->published()->create();

		$response = $this->orderTickets($concert, [
			'email' => 'jane@example.com',
			'ticket_quantity' => 3,
		]);

		$this->assertValidationError($response, 'payment_token');
	}
}
