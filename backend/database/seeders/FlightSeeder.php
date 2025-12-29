<?php

namespace Database\Seeders;

use App\Models\Flight;
use Illuminate\Database\Seeder;

class FlightSeeder extends Seeder
{
    public function run(): void
    {
        Flight::where('code', 'CA100')->delete();

        Flight::create([
            'code' => 'CA100',
            'seat_count' => 5,
            'booked_count' => 0,
        ]);
    }
}
