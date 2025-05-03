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
        Schema::create('villages', function (Blueprint $table) {
            $this->AddBaseFields($table);
            $table->unsignedBigInteger('province_id');
            $table->unsignedBigInteger('district_id');
            $table->unsignedBigInteger('commune_id');
            $table->string('name');
            $table->string('local_name');
            $table->foreign('province_id')->references('id')->on('provinces')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('district_id')->references('id')->on('districts')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('commune_id')->references('id')->on('communes')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('villages');
    }
};
