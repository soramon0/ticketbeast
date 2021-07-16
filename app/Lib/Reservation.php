<?php

namespace App\Lib;

class Reservation
{
	private $tickets;

	public function __construct($tickets)
	{
		$this->tickets = $tickets;
	}

	public function totalCost()
	{
		return $this->tickets->sum('price');
	}
}
