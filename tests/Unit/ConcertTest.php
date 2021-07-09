<?php

namespace Tests\Unit;

use App\Models\Concert;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase;

class ConcertTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_formatted_date()
    {
        // Create a concert with a known date
        $concert = Concert::create([
            'date' => Carbon::parse('2016-12-01 8:00pm'),
            'title' => 'The Red Chord',
            'subtitle' => 'with Animosity and Lethargy',
            'ticket_price' => 3250,
            'venue' => 'The Mosh Pit',
            'venue_address' => '123 Example Lane',
            'city' => 'Laraville',
            'state' => 'ON',
            'zip' => '17916',
            'additional_information' => 'For tickets, call (555) 555-5555.'
        ]);

        $date = $concert->formatted_date;

        $this->assertEquals('December 1, 2016', $date);
    }
}
