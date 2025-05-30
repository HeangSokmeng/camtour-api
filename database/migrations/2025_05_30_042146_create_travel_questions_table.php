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
        Schema::create('travel_questions', function (Blueprint $table) {
            $this->AddBaseFields($table);
            $table->string('location')->nullable();
            $table->string('category')->nullable();
            $table->text('question')->nullable();
            $table->longText('answer')->nullable();
            $table->json('media')->nullable();
            $table->json('links')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_questions');
    }
};
