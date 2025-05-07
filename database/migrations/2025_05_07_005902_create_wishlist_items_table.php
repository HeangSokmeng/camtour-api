<?php

use App\Traits\BaseMigrationField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use BaseMigrationField;

    public function up()
    {
        Schema::create('wishlist_items', function (Blueprint $table) {
            $this->AddBaseFields($table);
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('location_id');
            $table->unique(['user_id', 'location_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('wishlist_items');
    }
};
