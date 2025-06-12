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
        Schema::table('travel_requests', function (Blueprint $table) {
            $table->string('meal_preference')->nullable()->after('hotel_preference');
            $table->string('local_transportation')->nullable()->after('meal_preference');
            $table->decimal('total_meal_cost', 10, 2)->nullable()->after('total_estimated_cost');
            $table->decimal('total_local_transport_cost', 10, 2)->nullable()->after('total_meal_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_requests', function (Blueprint $table) {
            $table->dropColumn([
                'meal_preference',
                'local_transportation',
                'total_meal_cost',
                'total_local_transport_cost'
            ]);
        });
    }
};
