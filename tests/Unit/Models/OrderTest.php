<?php

namespace Tests\Unit;

use App\Models\Concert;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;


    public function test_creating_an_order_from_tickets_email_and_amount()
    {
        $concert = Concert::factory()->create()->addTickets(5);
        $this->assertEquals(5, $concert->ticketsRemaining());

        $order = Order::forTickets($concert->findTickets(3), 'john@example.com', 3600);


        $this->assertEquals('john@example.com', $order->email);
        $this->assertEquals(3600, $order->amount);
        $this->assertEquals(3, $order->ticketQuantity());
        $this->assertEquals(2, $concert->ticketsRemaining());
    }

    public function test_converting_to_an_array()
    {
        $concert = Concert::factory()->create(['ticket_price' => 1200])->addTickets(5);
        $order = $concert->orderTickets('jane@example.com', 5);

        $result = $order->ToArray();

        $this->assertEquals([
            'email' => 'jane@example.com',
            'ticket_quantity' => 5,
            'amount' => 6000,
        ], $result);
    }
}
