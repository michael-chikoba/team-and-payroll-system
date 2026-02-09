<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $existingColumns = Schema::getColumnListing('tasks');
        
        Schema::table('tasks', function (Blueprint $table) use ($existingColumns) {
            // Only add if not exists
            if (!in_array('started_at', $existingColumns)) {
                $table->timestamp('started_at')->nullable()->after('completed_at');
            }
            
            if (!in_array('estimated_hours', $existingColumns)) {
                $table->decimal('estimated_hours', 8, 2)->nullable()->after('started_at');
            }
            
            if (!in_array('actual_hours', $existingColumns)) {
                $table->decimal('actual_hours', 8, 2)->nullable()->after('estimated_hours');
            }
            
            if (!in_array('sla_hours', $existingColumns)) {
                $table->decimal('sla_hours', 8, 2)->nullable()->after('actual_hours');
            }
            
            if (!in_array('sla_breached', $existingColumns)) {
                $table->boolean('sla_breached')->default(false)->after('sla_hours');
            }
            
            if (!in_array('actual_completion_time', $existingColumns)) {
                $table->decimal('actual_completion_time', 8, 2)->nullable()->after('sla_breached');
            }
            
            if (!in_array('completed_on_time', $existingColumns)) {
                $table->boolean('completed_on_time')->nullable()->after('actual_completion_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $existingColumns = Schema::getColumnListing('tasks');
            
            $columnsToRemove = [
                'started_at',
                'estimated_hours',
                'actual_hours',
                'sla_hours',
                'sla_breached',
                'actual_completion_time',
                'completed_on_time'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (in_array($column, $existingColumns)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};