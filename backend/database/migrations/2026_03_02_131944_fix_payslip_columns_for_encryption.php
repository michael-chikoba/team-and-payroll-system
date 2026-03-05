<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            // Change decimal columns to text to store encrypted values
            $table->text('basic_salary')->nullable()->change();
            $table->text('gross_salary')->nullable()->change();
            $table->text('gross_pay')->nullable()->change();
            $table->text('net_pay')->nullable()->change();
            $table->text('total_deductions')->nullable()->change();
            $table->text('tax_deductions')->nullable()->change();
            
            // Also change these if you plan to encrypt them
            $table->text('napsa')->nullable()->change();
            $table->text('paye')->nullable()->change();
            $table->text('nhima')->nullable()->change();
            $table->text('pension')->nullable()->change();
            $table->text('other_deductions')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            // Revert back to decimal
            $table->decimal('basic_salary', 10, 2)->nullable()->change();
            $table->decimal('gross_salary', 10, 2)->nullable()->change();
            $table->decimal('gross_pay', 10, 2)->nullable()->change();
            $table->decimal('net_pay', 10, 2)->nullable()->change();
            $table->decimal('total_deductions', 10, 2)->nullable()->change();
            $table->decimal('tax_deductions', 10, 2)->nullable()->change();
            
            $table->decimal('napsa', 10, 2)->nullable()->change();
            $table->decimal('paye', 10, 2)->nullable()->change();
            $table->decimal('nhima', 10, 2)->nullable()->change();
            $table->decimal('pension', 10, 2)->nullable()->change();
            $table->decimal('other_deductions', 10, 2)->nullable()->change();
        });
    }
};