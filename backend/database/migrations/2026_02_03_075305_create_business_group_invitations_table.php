<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_group_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_group_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('invited_business_id')
                  ->constrained('businesses')
                  ->cascadeOnDelete();
            $table->foreignId('invited_by_user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            
            // Invitation details
            $table->enum('proposed_role', ['owner', 'member', 'partner'])->default('member');
            $table->text('message')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])
                  ->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->text('response_message')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('business_group_id');
            $table->index('invited_business_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_group_invitations');
    }
};