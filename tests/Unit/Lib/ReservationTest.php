<?php

namespace Tests\Unit;

use App\Lib\Reservation;
use App\Models\Ticket;
use Tests\TestCase;

class ReservationTest extends TestCase
{
	public function test_calculating_the_total_cost()
	{
		$tickets = collect([
			(object) ['price' => 1200],
			(object) ['price' => 1200],
			(object) ['price' => 1200],
		]);

		$reservation = new Reservation($tickets);

		$this->assertEquals(3600, $reservation->totalCost());
	}

	public function test_reserved_tickets_are_released_when_a_reservation_is_cancelled()
	{
		$tickets = collect([
			$this->spy(Ticket::class),
			$this->spy(Ticket::class),
			$this->spy(Ticket::class),
		]);
		$reservation = new Reservation($tickets);

		$reservation->cancel();

		foreach ($tickets as $ticket) {
			$ticket->shouldHaveReceived('release');
		}
	}
}
