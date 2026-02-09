<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_group_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_group_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('business_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            
            $table->string('action', 100);
            $table->text('description')->nullable();
            $table->string('entity_type', 50)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes
            $table->index('business_group_id');
            $table->index('business_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_group_activity_logs');
    }
};