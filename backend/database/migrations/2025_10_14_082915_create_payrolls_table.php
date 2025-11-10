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
       // 2024_01_07_000000_create_payrolls_table.php
Schema::create('payrolls', function (Blueprint $table) {
    $table->id();
    $table->string('payroll_period');
    $table->date('start_date');
    $table->date('end_date');
    $table->enum('status', ['draft', 'processing', 'completed', 'failed'])->default('draft');
    $table->decimal('total_gross', 12, 2)->default(0);
    $table->decimal('total_net', 12, 2)->default(0);
    $table->integer('employee_count')->default(0);
    $table->timestamp('processed_at')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
