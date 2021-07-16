<?php

namespace Tests\Unit;

use App\Lib\Reservation;
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
}
