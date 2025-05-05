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
        Schema::create('comments', function (Blueprint $table) {
            $this->AddBaseFields($table);

            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->text('comment')->nullable();
            $table->json('photos')->nullable();
            $table->boolean('status')->default(true);
            // Foreign key constraints
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
