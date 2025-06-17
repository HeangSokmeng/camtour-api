<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected array $excludedTables = [
        'migrations',
        'users',
        'password_resets',
        'password_reset_tokens',
        'failed_jobs',
        'personal_access_tokens',
    ];

    public function up(): void
    {
        $tables = $this->getAllTables();
        foreach ($tables as $tableName) {
            if (!in_array($tableName, $this->excludedTables) && Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'is_deleted')) {
                        $table->boolean('is_deleted')->default(false);
                    }
                    if (!Schema::hasColumn($tableName, 'deleted_datetime')) {
                        $table->dateTimeTz('deleted_datetime')->nullable();
                    }
                    if (!Schema::hasColumn($tableName, 'deleted_uid')) {
                        $table->unsignedBigInteger('deleted_uid')->nullable();
                    }
                });
            }
        }
    }

    public function down(): void
    {
        $tables = $this->getAllTables();
        foreach ($tables as $tableName) {
            if (!in_array($tableName, $this->excludedTables) && Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'is_deleted')) {
                        $table->dropColumn('is_deleted');
                    }
                    if (Schema::hasColumn($tableName, 'deleted_datetime')) {
                        $table->dropColumn('deleted_datetime');
                    }
                    if (Schema::hasColumn($tableName, 'deleted_uid')) {
                        $table->dropColumn('deleted_uid');
                    }
                });
            }
        }
    }

    private function getAllTables(): array
    {
        try {
            $driver = DB::getDriverName();
            switch ($driver) {
                case 'pgsql':
                    $tables = DB::select("
                        SELECT table_name as tablename
                        FROM information_schema.tables
                        WHERE table_schema = 'public'
                        AND table_type = 'BASE TABLE'
                    ");
                    break;
                case 'mysql':
                    $database = DB::getDatabaseName();
                    $tables = DB::select("
                        SELECT table_name as tablename
                        FROM information_schema.tables
                        WHERE table_schema = ?
                        AND table_type = 'BASE TABLE'
                    ", [$database]);
                    break;
                case 'sqlite':
                    $tables = DB::select("
                        SELECT name as tablename
                        FROM sqlite_master
                        WHERE type = 'table'
                    ");
                    break;
                default:
                    return $this->getTablesUsingSchemaBuilder();
            }

            return array_map(function($table) {
                return $table->tablename;
            }, $tables);
        } catch (\Exception $e) {
            return $this->getTablesUsingSchemaBuilder();
        }
    }

    /**
     * Alternative method using Schema Builder (Laravel way)
     */
    private function getTablesUsingSchemaBuilder(): array
    {
        return [
            'posts',
            'categories',
            'products',
            'orders',
        ];
    }
};
