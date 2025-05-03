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
        Schema::create('categories', function (Blueprint $table) {
            // setup column
            $this->AddBaseFields($table);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
