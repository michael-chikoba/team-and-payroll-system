<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            // Change from decimal to text to store encrypted values
            $table->text('base_salary')->nullable()->change();
            $table->text('transport_allowance')->nullable()->change();
            $table->text('lunch_allowance')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            // Revert back to decimal if needed
            $table->decimal('base_salary', 10, 2)->change();
            $table->decimal('transport_allowance', 10, 2)->default(0.00)->change();
            $table->decimal('lunch_allowance', 10, 2)->default(0.00)->change();
        });
    }
};