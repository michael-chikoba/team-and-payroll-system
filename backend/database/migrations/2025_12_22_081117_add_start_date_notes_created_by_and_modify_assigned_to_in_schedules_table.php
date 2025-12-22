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
        Schema::table('schedules', function (Blueprint $table) {
            // Check and add notes column if it doesn't exist
            if (!Schema::hasColumn('schedules', 'notes')) {
                $table->text('notes')->nullable()->after('metadata');
            }
            
            // Check and add created_by if it doesn't exist
            if (!Schema::hasColumn('schedules', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('completed_at');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
            
            // Add index on start_date if it doesn't exist
            $indexes = Schema::getIndexes('schedules');
            $hasStartDateIndex = false;
            foreach ($indexes as $index) {
                if (in_array('start_date', $index['columns'])) {
                    $hasStartDateIndex = true;
                    break;
                }
            }
            if (!$hasStartDateIndex && Schema::hasColumn('schedules', 'start_date')) {
                $table->index('start_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasColumn('schedules', 'notes')) {
                $table->dropColumn('notes');
            }
            
            if (Schema::hasColumn('schedules', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            
            $indexes = Schema::getIndexes('schedules');
            foreach ($indexes as $index) {
                if (in_array('start_date', $index['columns'])) {
                    $table->dropIndex($index['name']);
                    break;
                }
            }
        });
    }
};