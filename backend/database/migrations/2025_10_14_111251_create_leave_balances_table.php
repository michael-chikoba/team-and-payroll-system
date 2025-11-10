<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['vacation', 'sick', 'personal', 'maternity', 'paternity']);
            $table->decimal('balance', 5, 2)->default(0);
            $table->integer('year');
            $table->timestamps();
            
            $table->unique(['employee_id', 'type', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};