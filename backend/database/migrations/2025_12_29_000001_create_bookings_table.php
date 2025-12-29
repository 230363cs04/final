<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name');
            $table->timestamps();
            $table->index(['flight_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
}
