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
        Schema::table('attendances', function (Blueprint $table) {
            // Add new columns for overtime tracking
            $table->decimal('regular_hours', 5, 2)->default(0)->after('total_hours');
            $table->decimal('overtime_hours', 5, 2)->default(0)->after('regular_hours');
            $table->boolean('is_overtime_session')->default(false)->after('status');
            $table->unsignedBigInteger('parent_attendance_id')->nullable()->after('is_overtime_session');
            
            // Add foreign key for parent attendance
            $table->foreign('parent_attendance_id')
                ->references('id')
                ->on('attendances')
                ->onDelete('set null');
            
            // Add index for better query performance
            $table->index(['employee_id', 'date', 'is_overtime_session']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['parent_attendance_id']);
            $table->dropIndex(['employee_id', 'date', 'is_overtime_session']);
            $table->dropColumn([
                'regular_hours',
                'overtime_hours',
                'is_overtime_session',
                'parent_attendance_id'
            ]);
        });
    }
};