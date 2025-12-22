<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {

            // Add country_id only if it does NOT exist
            if (!Schema::hasColumn('attendances', 'country_id')) {
                $table->foreignId('country_id')->nullable()->after('employee_id')->constrained()->onDelete('set null');
                $table->index('country_id');
            }

            // Add business_id only if it does NOT exist
            if (!Schema::hasColumn('attendances', 'business_id')) {
                $table->foreignId('business_id')->nullable()->after('country_id')->constrained()->onDelete('set null');
                $table->index('business_id');
            }

            // Add composite index only if it does NOT exist
            // Laravel index names follow this pattern:
            // table_column1_column2_column3_index
            $compositeIndex = 'attendances_date_country_id_business_id_index';
            if (!Schema::hasIndex('attendances', $compositeIndex)) {
                $table->index(['date', 'country_id', 'business_id'], $compositeIndex);
            }
        });

        // Safely update attendances if columns exist
        if (
            Schema::hasColumn('attendances', 'country_id') &&
            Schema::hasColumn('attendances', 'business_id')
        ) {
            DB::statement('
                UPDATE attendances a
                INNER JOIN employees e ON a.employee_id = e.id
                SET a.country_id = e.country_id, a.business_id = e.business_id
                WHERE a.country_id IS NULL OR a.business_id IS NULL
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {

            if (Schema::hasColumn('attendances', 'country_id')) {
                $table->dropForeign(['country_id']);
                $table->dropIndex(['attendances_country_id_index']);
            }

            if (Schema::hasColumn('attendances', 'business_id')) {
                $table->dropForeign(['business_id']);
                $table->dropIndex(['attendances_business_id_index']);
            }

            $compositeIndex = 'attendances_date_country_id_business_id_index';
            if (Schema::hasIndex('attendances', $compositeIndex)) {
                $table->dropIndex([$compositeIndex]);
            }

            if (Schema::hasColumn('attendances', 'country_id')) {
                $table->dropColumn('country_id');
            }

            if (Schema::hasColumn('attendances', 'business_id')) {
                $table->dropColumn('business_id');
            }
        });
    }
};
