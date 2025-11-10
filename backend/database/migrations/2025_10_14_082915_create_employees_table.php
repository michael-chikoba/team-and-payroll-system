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
        // 2024_01_02_000000_create_employees_table.php
Schema::create('employees', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('manager_id')->nullable()->constrained('users');
    $table->string('employee_id')->unique();
    $table->string('position');
    $table->string('department');
    $table->decimal('base_salary', 10, 2);
    $table->date('hire_date');
    $table->enum('employment_type', ['full_time', 'part_time', 'contract']);
    $table->json('bank_details')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
