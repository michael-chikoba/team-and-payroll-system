<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // EMPLOYEES TABLE
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'country_id')) {
                $table->foreignId('country_id')
                      ->nullable()
                      ->after('id')
                      ->constrained()
                      ->nullOnDelete();
            }
        });

        // TABLES TO UPDATE
        $tables = ['attendances', 'leaves', 'payrolls', 'payslips'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'country_id')) {
                        $table->foreignId('country_id')
                              ->nullable()
                              ->after('id')
                              ->constrained()
                              ->nullOnDelete();
                    }
                });
            }
        }
    }

    public function down(): void
    {
        // EMPLOYEES TABLE
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'country_id')) {
                $table->dropForeign(['country_id']);
                $table->dropColumn('country_id');
            }
        });

        // OTHER TABLES
        $tables = ['attendances', 'leaves', 'payrolls', 'payslips'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'country_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['country_id']);
                    $table->dropColumn('country_id');
                });
            }
        }
    }
};
