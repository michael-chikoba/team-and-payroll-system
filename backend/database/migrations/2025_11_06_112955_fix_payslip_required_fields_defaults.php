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
            // Make sure all salary fields have defaults
            $table->decimal('basic_salary', 10, 2)->default(0)->change();
            $table->decimal('gross_pay', 10, 2)->default(0)->change();
            $table->decimal('net_pay', 10, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - these are safety defaults
    }
};