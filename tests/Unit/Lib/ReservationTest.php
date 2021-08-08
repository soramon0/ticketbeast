<?php

namespace Tests\Unit\Lib;

use App\Billing\FakePaymentGateway;
use App\Lib\Reservation;
use App\Models\Concert;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
	use RefreshDatabase;

	public function test_calculating_the_total_cost()
	{
		$tickets = collect([
			(object) ['price' => 1200],
			(object) ['price' => 1200],
			(object) ['price' => 1200],
		]);

		$reservation = new Reservation($tickets, 'john@example.com');

		$this->assertEquals(3600, $reservation->totalCost());
	}

	public function test_retrieving_the_reservations_tickets()
	{
		$tickets = collect([
			(object) ['price' => 1200],
			(object) ['price' => 1200],
			(object) ['price' => 1200],
		]);

		$reservation = new Reservation($tickets, 'john@example.com');

		$this->assertEquals($tickets, $reservation->tickets());
	}

	public function test_retrieving_the_customer_email()
	{
		$reservation = new Reservation(collect(), 'john@example.com');

		$this->assertEquals('john@example.com', $reservation->email());
	}

	public function test_reserved_tickets_are_released_when_a_reservation_is_cancelled()
	{
		$tickets = collect([
			$this->spy(Ticket::class),
			$this->spy(Ticket::class),
			$this->spy(Ticket::class),
		]);
		$reservation = new Reservation($tickets, 'john@example.com');

		$reservation->cancel();

		foreach ($tickets as $ticket) {
			$ticket->shouldHaveReceived('release');
		}
	}

	public function test_completing_a_reservation()
	{
		$concert = Concert::factory()->hasTickets(3)->create(['ticket_price' => 1200]);
		$reservation = new Reservation($concert->tickets, 'john@example.com');
		$paymentGateway = new FakePaymentGateway;

		$order = $reservation->complete($paymentGateway, $paymentGateway->getValidTestToken());

		$this->assertEquals('john@example.com', $order->email);
		$this->assertEquals(3, $order->ticketQuantity());
		$this->assertEquals(3600, $order->amount);
		$this->assertEquals(3600, $paymentGateway->totalCharges());
	}
}
