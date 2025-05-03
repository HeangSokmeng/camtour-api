<?php

use App\Traits\BaseMigrationField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{    use BaseMigrationField;

    public function up(): void
    {
        Schema::create('location_stars', function (Blueprint $table) {
            // setup column
            $this->AddBaseFields($table);
            $table->unsignedBigInteger('rater_id');
            $table->unsignedBigInteger('location_id');
            $table->string('comment')->nullable();
            $table->unsignedTinyInteger('star');

            // setup relationship
            $table->foreign('rater_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_stars');
    }
};
