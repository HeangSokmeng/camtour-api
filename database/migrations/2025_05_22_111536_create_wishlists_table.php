<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('item_id');
            $table->enum('item_type', ['location', 'product'])->default('location');
            $table->json('item_data')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'item_id', 'item_type'], 'unique_user_item');

            $table->index(['user_id', 'created_at']);
            $table->index('item_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_wishlists');
    }
};
