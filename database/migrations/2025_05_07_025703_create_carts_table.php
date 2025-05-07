<?php

use App\Traits\BaseMigrationField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    use BaseMigrationField;

    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $this->AddBaseFields($table);
            $table->unsignedBigInteger('user_id');
            $table->string('status')->default('active');
            $table->integer('total_items')->default(0);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('carts');
    }
}
