<?php

namespace App\Lib;

use App\Billing\PaymentGateway;
use App\Models\Order;

class Reservation
{
	private $tickets;
	private $email;

	public function __construct($tickets, String $email)
	{
		$this->tickets = $tickets;
		$this->email = $email;
	}

	public function totalCost()
	{
		return $this->tickets->sum('price');
	}

	public function tickets()
	{
		return $this->tickets;
	}

	public function email()
	{
		return $this->email;
	}

	public function complete(PaymentGateway $paymentGateway, String $paymentToken)
	{
		$paymentGateway->charge($this->totalCost(), $paymentToken);
		return Order::forTickets($this->tickets(), $this->email(), $this->totalCost());
	}

	public function cancel()
	{
		foreach ($this->tickets as $ticket) {
			$ticket->release();
		}
	}
}
