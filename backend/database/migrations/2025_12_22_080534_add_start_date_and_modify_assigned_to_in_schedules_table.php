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
            // Add start_date column after the priority column
            $table->dateTime('start_date')->nullable()->after('priority');
            
            // Also fix assigned_to to be unsignedBigInteger instead of string
            $table->unsignedBigInteger('assigned_to')->nullable()->change();
            
            // Add index for start_date for better query performance
            $table->index('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex(['start_date']);
            $table->dropColumn('start_date');
            
            // Revert assigned_to back to string
            $table->string('assigned_to')->nullable()->change();
        });
    }
};