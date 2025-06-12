<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('travel_requests', function (Blueprint $table) {
            $table->id();
            $table->decimal('budget', 10, 2);
            $table->string('transportation')->nullable();
            $table->string('departure_location')->nullable();
            $table->integer('trip_duration')->nullable();
            $table->integer('party_size')->nullable();
            $table->string('age_range')->nullable();
            $table->string('primary_destination')->nullable();
            $table->string('hotel_preference')->nullable();
            $table->json('user_answers')->nullable(); // store all answers as JSON
            $table->text('recommendation')->nullable();
            $table->decimal('total_estimated_cost', 10, 2)->nullable();
            $table->json('recommended_itinerary')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_requests');
    }
};
