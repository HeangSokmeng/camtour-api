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
        try {
            $tables = $this->getTablesFromInformationSchema();
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
        } catch (\Exception $e) {
            $this->addSoftDeleteColumnsToKnownTables();
        }
    }

    public function down(): void
    {
        try {
            $tables = $this->getTablesFromInformationSchema();
            foreach ($tables as $tableName) {
                if (!in_array($tableName, $this->excludedTables) && Schema::hasTable($tableName)) {
                    Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                        $columns = ['is_deleted', 'deleted_datetime', 'deleted_uid'];

                        foreach ($columns as $column) {
                            if (Schema::hasColumn($tableName, $column)) {
                                $table->dropColumn($column);
                            }
                        }
                    });
                }
            }
        } catch (\Exception $e) {
            // Fallback for rollback
            $this->removeSoftDeleteColumnsFromKnownTables();
        }
    }

    /**
     * Get tables using standard information_schema (more compatible)
     */
    private function getTablesFromInformationSchema(): array
    {
        $database = DB::connection()->getDatabaseName();
        $tables = DB::select("
            SELECT table_name
            FROM information_schema.tables
            WHERE table_catalog = ?
            AND table_schema = 'public'
            AND table_type = 'BASE TABLE'
        ", [$database]);
        return array_map(function($table) {
            return $table->table_name;
        }, $tables);
    }
    private function addSoftDeleteColumnsToKnownTables(): void
    {
        $knownTables = [
            'posts',
            'categories',
            'products',
            'orders',
            'comments',
        ];

        foreach ($knownTables as $tableName) {
            if (Schema::hasTable($tableName)) {
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

    private function removeSoftDeleteColumnsFromKnownTables(): void
    {
        $knownTables = [
            'posts',
            'categories',
            'products',
            'orders',
            'comments',
        ];

        foreach ($knownTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $columns = ['is_deleted', 'deleted_datetime', 'deleted_uid'];
                    foreach ($columns as $column) {
                        if (Schema::hasColumn($tableName, $column)) {
                            $table->dropColumn($column);
                        }
                    }
                });
            }
        }
    }
};
