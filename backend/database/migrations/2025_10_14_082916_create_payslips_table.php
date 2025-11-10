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
       // 2024_01_08_000000_create_payslips_table.php
Schema::create('payslips', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained()->onDelete('cascade');
    $table->foreignId('payroll_id')->constrained()->onDelete('cascade');
    $table->decimal('basic_salary', 10, 2);
    $table->decimal('overtime_pay', 10, 2)->default(0);
    $table->decimal('bonuses', 10, 2)->default(0);
    $table->decimal('gross_pay', 10, 2);
    $table->decimal('tax_deductions', 10, 2)->default(0);
    $table->decimal('other_deductions', 10, 2)->default(0);
    $table->decimal('net_pay', 10, 2);
    $table->json('breakdown')->nullable();
    $table->string('pdf_path')->nullable();
    $table->boolean('is_sent')->default(false);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
