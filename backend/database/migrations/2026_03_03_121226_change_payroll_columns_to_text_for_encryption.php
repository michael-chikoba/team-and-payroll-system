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
        Schema::table('payrolls', function (Blueprint $table) {
            // Change financial columns from decimal to text
            $table->text('total_gross')->nullable()->change();
            $table->text('total_net')->nullable()->change();
            // employee_count can stay as integer
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('total_gross', 15, 2)->nullable()->change();
            $table->decimal('total_net', 15, 2)->nullable()->change();
        });
    }
};