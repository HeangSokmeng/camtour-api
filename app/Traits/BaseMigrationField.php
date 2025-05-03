<?php
namespace App\Traits;
use Illuminate\Database\Schema\Blueprint;
trait BaseMigrationField{
    public function AddBaseFields(Blueprint $table,$useDelete=true){
        $table->id();
        $table->timestamp("created_at")->useCurrent();
        $table->timestamp("updated_at")->useCurrent()->useCurrentOnUpdate();
        $table->unsignedBigInteger('create_uid')->nullable();
        $table->unsignedBigInteger('update_uid')->nullable();


        $table->foreign('create_uid')->references('id')->on('users');
        $table->foreign('update_uid')->references('id')->on('users');


        if($useDelete){
            $table->boolean('is_deleted')->default(0);
            $table->unsignedBigInteger('deleted_uid')->nullable();
            $table->dateTimeTz('deleted_datetime')->nullable();
            $table->string('delete_notes')->nullable();
        }
    }
}
