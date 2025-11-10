<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, drop the existing table
        Schema::dropIfExists('leave_balances');
        
        // Recreate the table with correct enum values
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['annual', 'sick', 'maternity', 'paternity', 'unpaid', 'bereavement'])->default('annual');
            $table->decimal('balance', 8, 2)->default(0);
            $table->decimal('allocated_days', 8, 2)->default(0);
            $table->decimal('used_days', 8, 2)->default(0);
            $table->decimal('carried_over', 8, 2)->default(0);
            $table->year('year');
            $table->timestamps();

            // Unique constraint to prevent duplicate balances for same employee, type, and year
            $table->unique(['employee_id', 'type', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};