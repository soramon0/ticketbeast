<?php

namespace Tests\Unit;

use App\Lib\Reservation;
use App\Models\Concert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
	use RefreshDatabase;


	public function test_calculating_the_total_cost()
	{
		$concert = Concert::factory()->create(['ticket_price' => 1200])->addTickets(3);
		$tickets = $concert->findTickets(3);

		$reservation = new Reservation($tickets);

		$this->assertEquals(3600, $reservation->totalCost());
	}
}
