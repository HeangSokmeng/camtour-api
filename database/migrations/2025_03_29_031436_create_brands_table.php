<?php

use App\Traits\BaseMigrationField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    use BaseMigrationField;

    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $this->AddBaseFields($table);
            $table->string('name', 50)->nullable();
            $table->string('name_km', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
