<?php

use App\Traits\BaseMigrationField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    use BaseMigrationField;

    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_deleted')->default(false);
            $table->dateTimeTz('deleted_datetime')->nullable();
            $table->unsignedBigInteger('deleted_uid')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $this->AddBaseFields($table);
            $table->unsignedBigInteger('role_id');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('gender')->nullable();
            $table->string('image')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->unique();
            $table->enum('is_lock', ['lock', 'unlock'])->default('unlock');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $this->AddBaseFields($table);
            // $table->unsignedSmallInteger('id', true)->primary();
            $table->string('name')->unique();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }
    public function down(): void
    {
        // Drop FKs from product_categories
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropForeign(['create_uid']);
            $table->dropForeign(['update_uid']);
        });

        // Drop FKs from users
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
        });

        // Drop FKs from role_user
        Schema::table('role_user', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['user_id']);
        });

        // Drop tables in dependency-safe order
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }

};
