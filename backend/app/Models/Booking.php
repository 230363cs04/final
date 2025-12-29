<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = ['flight_id', 'customer_name'];

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }
}
