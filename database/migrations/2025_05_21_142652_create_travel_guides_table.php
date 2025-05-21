<?php

use App\Traits\BaseMigrationField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    use BaseMigrationField;

    public function up(): void
    {
        Schema::create('travel_guides', function (Blueprint $table) {
            $this->AddBaseFields($table);
            $table->unsignedBigInteger('location_id');
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('best_time_to_visit')->nullable();
            $table->json('local_contacts')->nullable();
            $table->json('currency_and_budget')->nullable();
            $table->json('local_transportation')->nullable();
            $table->json('what_to_pack')->nullable();
            $table->json('local_etiquette')->nullable();
            $table->json('what_on_sale')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_guides');
    }
};
