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
        Schema::create('provinces', function (Blueprint $table) {
            $this->AddBaseFields($table);
            $table->string('name');
            $table->string('local_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
