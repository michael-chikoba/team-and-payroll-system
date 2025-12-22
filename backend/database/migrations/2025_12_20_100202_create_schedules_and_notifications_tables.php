<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', [
                'banner_creation',
                'weekly_overview',
                'test_sequences',
                'live_games',
                'multibets',
                'news_updates',
                'other'
            ]);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'overdue'])
                  ->default('pending');
            $table->enum('priority', ['low', 'moderate', 'high', 'urgent'])
                  ->default('moderate');
            $table->dateTime('due_date');
            $table->string('assigned_to')->nullable();
            $table->json('metadata')->nullable(); // For banner regions, etc.
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index('due_date');
            $table->index('status');
            $table->index('type');
        });

        Schema::create('schedule_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['assignment', 'reminder', 'overdue', 'completed']);
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            
            $table->index('is_read');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_notifications');
        Schema::dropIfExists('schedules');
    }
};