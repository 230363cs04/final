<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Flight extends Model
{
    protected $fillable = ['code', 'seat_count', 'booked_count'];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function seatsAvailable(): int
    {
        return max(0, $this->seat_count - $this->booked_count);
    }
}
