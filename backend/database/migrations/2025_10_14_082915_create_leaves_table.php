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
       // 2024_01_05_000000_create_leaves_table.php
Schema::create('leaves', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained()->onDelete('cascade');
    $table->foreignId('manager_id')->constrained('users');
    $table->enum('type', ['vacation', 'sick', 'personal', 'maternity', 'paternity']);
    $table->date('start_date');
    $table->date('end_date');
    $table->integer('total_days');
    $table->text('reason');
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->text('manager_notes')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
