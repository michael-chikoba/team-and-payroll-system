<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_work_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('hours', 8, 2);
            $table->text('description');
            $table->date('work_date')->default(now());
            $table->timestamps();
            
            $table->index(['task_id', 'user_id']);
            $table->index('work_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_work_logs');
    }
};