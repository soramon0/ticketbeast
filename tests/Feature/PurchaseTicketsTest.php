<?php

namespace Tests\Feature;

use App\Billing\FakePaymentGateway;
use App\Billing\PaymentGateway;
use App\Models\Concert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PurchaseTicketsTest extends TestCase
{
	use RefreshDatabase;

	private FakePaymentGateway $paymentGateway;

	protected function setUp(): void
	{
		parent::setUp();

		$this->paymentGateway = new FakePaymentGateway;
		$this->app->instance(PaymentGateway::class, $this->paymentGateway);
	}

	function orderTickets($concert, $params)
	{
		$savedRequest = $this->app['request'];
		$response = $this->json('POST', "/concerts/{$concert->id}/orders", $params);
		$this->app['request'] = $savedRequest;
		return $response;
	}

	function assertValidationError($response, $field)
	{
		$response->assertStatus(422);
		$this->assertArrayHasKey($field, $response->json()['errors']);
	}

	function test_customer_can_purchase_tickets_to_published_concert()
	{
		$concert = Concert::factory()->published()->create(['ticket_price' => 3250])->addTickets(3);

		$response = $this->orderTickets($concert, [
			'email' => 'john@example.com',
			'ticket_quantity' => 3,
			'payment_token' => $this->paymentGateway->getValidTestToken(),
		]);

		$response->assertStatus(201);
		$response->assertJson(
			fn (AssertableJson $json) =>
			$json->where('email', 'john@example.com')
				->where('ticket_quantity', 3)
				->where('amount', 9750)
		);

		$this->assertEquals(9750, $this->paymentGateway->totalCharges());
		$this->assertTrue($concert->hasOrderFor('john@example.com'));
		$this->assertEquals(3, $concert->ordersFor('john@example.com')->first()->ticketQuantity());
	}

	function test_customer_cannot_purchase_tickets_to_an_unpublished_concert()
	{
		$concert = Concert::factory()->unpublished()->create()->addTickets(3);

		$response = $this->orderTickets($concert, [
			'email' => 'john@example.com',
			'ticket_quantity' => 3,
			'payment_token' => $this->paymentGateway->getValidTestToken(),
		]);

		$response->assertStatus(404);
		$this->assertFalse($concert->hasOrderFor('john@example.com'));
		$this->assertEquals(0, $this->paymentGateway->totalCharges());
	}

	function test_cannot_purchase_tickets_another_customer_is_already_trying_to_purchase()
	{
		$concert = Concert::factory()->published()->create(['ticket_price' => 1200])->addTickets(3);

		$this->paymentGateway->beforeFirstCharge(function ($paymentGateway) use ($concert) {
			$response =	$this->orderTickets($concert, [
				'email' => 'personB@example.com',
				'ticket_quantity' => 1,
				'payment_token' => $paymentGateway->getValidTestToken(),
			]);

			$response->assertStatus(422);
			$this->assertFalse($concert->hasOrderFor('personB@example.com'));
			$this->assertEquals(0, $paymentGateway->totalCharges());
		});

		$this->orderTickets($concert, [
			'email' => 'personA@example.com',
			'ticket_quantity' => 3,
			'payment_token' => $this->paymentGateway->getValidTestToken(),
		]);

		$this->assertEquals(3600, $this->paymentGateway->totalCharges());
		$this->assertTrue($concert->hasOrderFor('personA@example.com'));
		$this->assertEquals(3, $concert->ordersFor('personA@example.com')->first()->ticketQuantity());
	}

	function test_an_order_is_not_created_if_payment_fails()
	{
		$concert = Concert::factory()->published()->create(['ticket_price' => 3250])->addTickets(3);

		$response = $this->orderTickets($concert, [
			'email' => 'john@example.com',
			'ticket_quantity' => 3,
			'payment_token' => 'invalid-payment-token',
		]);

		$response->assertStatus(422);
		$this->assertFalse($concert->hasOrderFor('john@example.com'));
	}

	function test_cannot_purchase_more_tickets_than_remaining()
	{
		$concert = Concert::factory()->published()->create()->addTickets(50);

		$response = $this->orderTickets($concert, [
			'email' => 'john@example.com',
			'ticket_quantity' => 51,
			'payment_token' => $this->paymentGateway->getValidTestToken(),
		]);

		$response->assertStatus(422);

		$this->assertFalse($concert->hasOrderFor('john@example.com'));
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
