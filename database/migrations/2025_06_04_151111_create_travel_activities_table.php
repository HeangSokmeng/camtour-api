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
        Schema::create('travel_activities', function (Blueprint $table) {
            $this->AddBaseFields($table);
            $table->unsignedBigInteger('location_id');
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnUpdate()->cascadeOnDelete();

            $table->string('image')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->integer('duration_hours')->nullable();
            $table->enum('difficulty_level', ['Easy', 'Moderate', 'Hard'])->default('Easy');
            $table->decimal('price_per_person', 8, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->integer('max_participants')->nullable();
            $table->json('included_items')->nullable();
            $table->json('requirements')->nullable();
            $table->softDeletes();

            $table->index(['location_id', 'difficulty_level']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_activities');
    }
};
