<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Flight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function unsafeBook(Request $request)
    {
        $flightCode = $request->input('flight', 'CA100');
        $name = $request->input('name', 'Guest');

        $flight = Flight::where('code', $flightCode)->firstOrFail();

        if ($flight->booked_count < $flight->seat_count) {
            usleep(random_int(5000, 50000));

            $flight->booked_count += 1;
            $flight->save();

            Booking::create([
                'flight_id' => $flight->id,
                'customer_name' => $name,
            ]);

            return response()->json([
                'status' => 'ok',
                'message' => 'Booking confirmed (UNSAFE).',
                'booked_count' => $flight->booked_count,
            ]);
        }

        return response()->json([
            'status' => 'full',
            'message' => 'Flight full (UNSAFE).',
            'booked_count' => $flight->booked_count,
        ], 409);
    }

    public function safeBook(Request $request)
    {
        $flightCode = $request->input('flight', 'CA100');
        $name = $request->input('name', 'Guest');

        try {
            return DB::transaction(function () use ($flightCode, $name) {
                $flight = Flight::where('code', $flightCode)->lockForUpdate()->firstOrFail();

                if ($flight->booked_count >= $flight->seat_count) {
                    return response()->json([
                        'status' => 'full',
                        'message' => 'Flight full (SAFE).',
                        'booked_count' => $flight->booked_count,
                    ], 409);
                }

                usleep(random_int(5000, 50000));

                $flight->booked_count += 1;
                $flight->save();

                Booking::create([
                    'flight_id' => $flight->id,
                    'customer_name' => $name,
                ]);

                return response()->json([
                    'status' => 'ok',
                    'message' => 'Booking confirmed (SAFE).',
                    'booked_count' => $flight->booked_count,
                ]);
            }, 3);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to book safely: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function status(Request $request)
    {
        $flightCode = $request->input('flight', 'CA100');
        $flight = Flight::where('code', $flightCode)->firstOrFail();

        return response()->json([
            'flight' => $flight->code,
            'seat_count' => $flight->seat_count,
            'booked_count' => $flight->booked_count,
            'seats_available' => $flight->seatsAvailable(),
            'bookings' => $flight->bookings()->count(),
        ]);
    }
}
