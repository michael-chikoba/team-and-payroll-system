<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Alternative: Use LONGTEXT which can handle encryption better
        Schema::table('businesses', function (Blueprint $table) {
            // Drop unique constraint temporarily
            $table->dropUnique(['registration_number']);
            
            // Use longtext for encrypted data
            $table->longText('registration_number')->nullable()->change();
            $table->longText('tax_identification_number')->nullable()->change();
            $table->longText('phone')->nullable()->change();
            $table->longText('email')->nullable()->change();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->longText('phone')->nullable()->change();
            $table->longText('national_id')->nullable()->change();
            $table->longText('emergency_contact')->nullable()->change();
            $table->longText('bank_details')->nullable()->change();
            // address can stay as TEXT
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->longText('basic_salary')->nullable()->change();
            $table->longText('gross_pay')->nullable()->change();
            $table->longText('net_pay')->nullable()->change();
            $table->longText('tax_deductions')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Revert to original types
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('registration_number', 255)->nullable()->unique()->change();
            $table->string('tax_identification_number', 255)->nullable()->change();
            $table->string('phone', 255)->nullable()->change();
            $table->string('email', 255)->change();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('phone', 255)->nullable()->change();
            $table->string('national_id', 255)->nullable()->change();
            $table->string('emergency_contact', 255)->nullable()->change();
            $table->json('bank_details')->nullable()->change();
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('basic_salary', 10, 2)->change();
            $table->decimal('gross_pay', 10, 2)->change();
            $table->decimal('net_pay', 10, 2)->change();
            $table->decimal('tax_deductions', 10, 2)->change();
        });
    }
};