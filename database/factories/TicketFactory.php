<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
	protected $model = Ticket::class;

	public function definition()
	{
		return [
			'concert_id' => User::factory(),
		];
	}

	public function reserved()
	{
		return $this->state(function (array $attributes) {
			return [
				'reserved_at' => Carbon::now(),
			];
		});
	}
}
