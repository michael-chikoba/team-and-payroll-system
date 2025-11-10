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
            // Add period and date fields after payroll_id
            $table->date('pay_period_start')->nullable()->after('payroll_id');
            $table->date('pay_period_end')->nullable()->after('pay_period_start');
            $table->date('payment_date')->nullable()->after('pay_period_end');

            // Add allowance fields after basic_salary
            $table->decimal('house_allowance', 10, 2)->default(0)->after('basic_salary');
            $table->decimal('transport_allowance', 10, 2)->default(0)->after('house_allowance');
            $table->decimal('other_allowances', 10, 2)->default(0)->after('transport_allowance');

            // Add overtime breakdown fields before existing overtime_pay
            $table->decimal('overtime_hours', 5, 2)->default(0)->after('other_allowances');
            $table->decimal('overtime_rate', 10, 2)->default(0)->after('overtime_hours');
            $table->decimal('overtime_pay', 10, 2)->default(0)->change(); // Ensure it exists, but already does

            // Add gross_salary after bonuses (to align with gross_pay if needed)
            $table->decimal('gross_salary', 10, 2)->default(0)->after('bonuses');

            // Add specific deduction fields after tax_deductions
            $table->decimal('napsa', 10, 2)->default(0)->after('tax_deductions');
            $table->decimal('paye', 10, 2)->default(0)->after('napsa');
            $table->decimal('nhima', 10, 2)->default(0)->after('paye');

            // other_deductions already exists, so add total_deductions before net_pay
            $table->decimal('total_deductions', 10, 2)->default(0)->after('other_deductions');

            // pdf_path, is_sent, breakdown already exist, status added in previous migration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn([
                'pay_period_start',
                'pay_period_end',
                'payment_date',
                'house_allowance',
                'transport_allowance',
                'other_allowances',
                'overtime_hours',
                'overtime_rate',
                'gross_salary',
                'napsa',
                'paye',
                'nhima',
                'total_deductions',
            ]);
        });
    }
};