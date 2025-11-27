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
        Schema::create('country_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->onDelete('cascade');
            
            // Working hours configuration
            $table->decimal('working_hours_per_day', 4, 2)->default(8.00);
            $table->integer('working_days_per_week')->default(5);
            $table->json('working_days')->nullable()->comment('Array of working days: [1,2,3,4,5]');
            $table->time('work_start_time')->default('09:00:00');
            $table->time('work_end_time')->default('17:00:00');
            
            // Overtime configuration
            $table->decimal('overtime_multiplier', 4, 2)->default(1.5);
            $table->decimal('weekend_multiplier', 4, 2)->default(2.0);
            $table->decimal('holiday_multiplier', 4, 2)->default(2.5);
            
            // Payroll configuration
            $table->enum('payroll_frequency', ['weekly', 'bi-weekly', 'semi-monthly', 'monthly'])->default('monthly');
            $table->integer('payroll_day')->default(25)->comment('Day of month for monthly payroll');
            
            // Tax configuration
            $table->enum('tax_calculation_type', ['progressive', 'flat', 'none'])->default('progressive');
            $table->json('tax_brackets')->nullable()->comment('Progressive tax brackets');
            $table->decimal('flat_tax_rate', 5, 2)->nullable()->comment('Flat tax rate percentage');
            
            // Statutory deductions
            $table->json('statutory_deductions')->nullable()->comment('Pension, NHIMA, etc.');
            
            // Leave configuration
            $table->integer('annual_leave_days')->default(24);
            $table->integer('sick_leave_days')->default(12);
            $table->integer('maternity_leave_days')->default(90);
            $table->integer('paternity_leave_days')->default(14);
            
            // Public holidays
            $table->json('public_holidays')->nullable()->comment('Array of public holidays with dates');
            
            // Compliance
            $table->string('minimum_wage')->nullable();
            $table->json('compliance_requirements')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->unique('country_id');
            $table->index('payroll_frequency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('country_configurations');
    }
};