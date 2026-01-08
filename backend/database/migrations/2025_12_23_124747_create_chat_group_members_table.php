<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_group_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['admin', 'member'])->default('member');
            $table->timestamp('last_read_at')->nullable();
            $table->boolean('is_muted')->default(false);
            $table->timestamp('muted_until')->nullable();
            $table->timestamps();
            
            // Prevent duplicate memberships
            $table->unique(['chat_group_id', 'user_id']);
            
            // Indexes
            $table->index('user_id');
            $table->index(['chat_group_id', 'last_read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_group_members');
    }
};