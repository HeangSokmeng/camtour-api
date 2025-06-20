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
        Schema::create('tags', function (Blueprint $table) {
            // setup column    use BaseMigrationField;
            $this->AddBaseFields($table);
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
