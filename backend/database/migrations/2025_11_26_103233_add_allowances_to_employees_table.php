<?php

// New migration file: database/migrations/xxxx_xx_xx_add_allowances_to_employees_table.php
// Run: php artisan make:migration add_allowances_to_employees_table --table=employees

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
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('transport_allowance', 10, 2)->default(0)->after('base_salary');
            $table->decimal('lunch_allowance', 10, 2)->default(0)->after('transport_allowance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'transport_allowance',
                'lunch_allowance'
            ]);
        });
    }
};