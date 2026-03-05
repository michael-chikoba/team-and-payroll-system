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
        Schema::table('payslips', function (Blueprint $table) {
            // Change all financial columns from decimal to text
            $table->text('basic_salary')->nullable()->change();
            $table->text('house_allowance')->nullable()->change();
            $table->text('transport_allowance')->nullable()->change();
            $table->text('other_allowances')->nullable()->change();
            $table->text('overtime_rate')->nullable()->change();
            $table->text('overtime_pay')->nullable()->change();
            $table->text('gross_salary')->nullable()->change();
            $table->text('gross_pay')->nullable()->change();
            $table->text('tax_deductions')->nullable()->change();
            $table->text('napsa')->nullable()->change();
            $table->text('paye')->nullable()->change();
            $table->text('nhima')->nullable()->change();
            $table->text('pension')->nullable()->change();
            $table->text('other_deductions')->nullable()->change();
            $table->text('total_deductions')->nullable()->change();
            $table->text('net_pay')->nullable()->change();
            
            // Keep these as is (not encrypted)
            // overtime_hours - can stay as decimal/integer
            // bonuses - can stay as decimal
            // status - string
            // breakdown - json
            // pdf_path - string
            // is_sent - boolean
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            // Revert back to decimal (WARNING: Only run this if data is decrypted first!)
            $table->decimal('basic_salary', 10, 2)->nullable()->change();
            $table->decimal('house_allowance', 10, 2)->nullable()->change();
            $table->decimal('transport_allowance', 10, 2)->nullable()->change();
            $table->decimal('other_allowances', 10, 2)->nullable()->change();
            $table->decimal('overtime_rate', 10, 2)->nullable()->change();
            $table->decimal('overtime_pay', 10, 2)->nullable()->change();
            $table->decimal('gross_salary', 10, 2)->nullable()->change();
            $table->decimal('gross_pay', 10, 2)->nullable()->change();
            $table->decimal('tax_deductions', 10, 2)->nullable()->change();
            $table->decimal('napsa', 10, 2)->nullable()->change();
            $table->decimal('paye', 10, 2)->nullable()->change();
            $table->decimal('nhima', 10, 2)->nullable()->change();
            $table->decimal('pension', 10, 2)->nullable()->change();
            $table->decimal('other_deductions', 10, 2)->nullable()->change();
            $table->decimal('total_deductions', 10, 2)->nullable()->change();
            $table->decimal('net_pay', 10, 2)->nullable()->change();
        });
    }
};