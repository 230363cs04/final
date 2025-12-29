<?php

namespace App\Console\Commands;

use App\Models\Flight;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SimulateOverbooking extends Command
{
    protected $signature = 'simulate:overbooking 
                            {--endpoint=unsafe : unsafe|safe} 
                            {--attempts=10 : Number of concurrent bookings}
                            {--flight=CA100 : Flight code}';

    protected $description = 'Simulate concurrent booking requests to demonstrate race condition vs synchronized protection';

    public function handle(): int
    {
        $endpoint = $this->option('endpoint');
        $attempts = (int) $this->option('attempts');
        $flight = (string) $this->option('flight');

        $base = config('app.url') ?: 'http://127.0.0.1:8000';
        $url = rtrim($base, '/') . '/api/' . ($endpoint === 'safe' ? 'safe-book' : 'unsafe-book');

        // Reset counts for fair test
        $model = Flight::where('code', $flight)->firstOrFail();
        $model->booked_count = 0;
        $model->save();
        $model->bookings()->delete();

        $this->info("Simulating {$attempts} concurrent {$endpoint} bookings to {$url} for flight {$flight}...");

        $responses = Http::pool(function ($pool) use ($attempts, $url, $flight) {
            $reqs = [];
            for ($i = 1; $i <= $attempts; $i++) {
                $reqs[] = $pool->as("r{$i}")->post($url, [
                    'flight' => $flight,
                    'name' => "User{$i}",
                ]);
            }
            return $reqs;
        });

        $ok = 0; $full = 0; $err = 0;
        foreach ($responses as $key => $resp) {
            if (!$resp->successful() && $resp->status() !== 409) {
                $err++;
                continue;
            }
            $json = $resp->json();
            if (($json['status'] ?? '') === 'ok') $ok++;
            elseif (($json['status'] ?? '') === 'full') $full++;
            else $err++;
        }

        $statusUrl = rtrim($base, '/') . '/api/status';
        $status = Http::get($statusUrl, ['flight' => $flight])->json();

        $this->line('Results:');
        $this->line("- Confirmed OK: {$ok}");
        $this->line("- Rejected FULL: {$full}");
        $this->line("- Errors: {$err}");
        $this->line("- Final booked_count in DB: " . ($status['booked_count'] ?? 'n/a') . " / " . ($status['seat_count'] ?? 'n/a'));

        if ($endpoint === 'unsafe') {
            $this->warn('Expect POSSIBLE OVERBOOKING: booked_count may exceed seat_count!');
        } else {
            $this->info('Expect NO OVERBOOKING: booked_count should never exceed seat_count.');
        }

        return self::SUCCESS;
    }
}
