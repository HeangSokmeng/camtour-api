<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected array $excludedTables = [
        'migrations', 'users', 'password_resets',
    ];

    public function up(): void
    {
        $tables = DB::select("
            SELECT tablename
            FROM pg_tables
            WHERE schemaname = 'public'
        ");

        foreach ($tables as $table) {
            $tableName = $table->tablename;

            if (!in_array($tableName, $this->excludedTables)) {
                // Check if the table exists before applying changes
                if (Schema::hasTable($tableName)) {
                    Schema::table($tableName, function (Blueprint $tableBlueprint) use ($tableName) {
                        if (!Schema::hasColumn($tableName, 'is_deleted')) {
                            $tableBlueprint->boolean('is_deleted')->default(false);
                        }
                        if (!Schema::hasColumn($tableName, 'deleted_datetime')) {
                            $tableBlueprint->dateTimeTz('deleted_datetime')->nullable();
                        }
                        if (!Schema::hasColumn($tableName, 'deleted_uid')) {
                            $tableBlueprint->unsignedBigInteger('deleted_uid')->nullable();
                        }
                    });
                }
            }
        }
    }

    public function down(): void
    {
        $tables = DB::select("
            SELECT tablename
            FROM pg_tables
            WHERE schemaname = 'public'
        ");

        foreach ($tables as $table) {
            $tableName = $table->tablename;

            if (!in_array($tableName, $this->excludedTables)) {
                if (Schema::hasTable($tableName)) {
                    Schema::table($tableName, function (Blueprint $tableBlueprint) {
                        if (Schema::hasColumn($tableBlueprint->getTable(), 'is_deleted')) {
                            $tableBlueprint->dropColumn('is_deleted');
                        }
                        if (Schema::hasColumn($tableBlueprint->getTable(), 'deleted_datetime')) {
                            $tableBlueprint->dropColumn('deleted_datetime');
                        }
                        if (Schema::hasColumn($tableBlueprint->getTable(), 'deleted_uid')) {
                            $tableBlueprint->dropColumn('deleted_uid');
                        }
                    });
                }
            }
        }
    }
};
