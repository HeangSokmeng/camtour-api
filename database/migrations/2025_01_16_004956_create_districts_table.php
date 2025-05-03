<?php

use App\Traits\BaseMigrationField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use BaseMigrationField;

    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $this->AddBaseFields($table);
            $table->unsignedBigInteger('province_id');
            $table->string('name');
            $table->string('local_name');
            $table->foreign('province_id')->references('id')->on('provinces')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
