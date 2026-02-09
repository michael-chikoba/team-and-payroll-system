<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_group_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_group_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('business_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            // Membership Details
            $table->enum('role', ['owner', 'member', 'partner'])->default('member');
            $table->boolean('is_primary')->default(false);
            $table->timestamp('joined_at')->useCurrent();
            
            // Permissions within the group
            $table->boolean('can_manage_group')->default(false);
            $table->boolean('can_invite_businesses')->default(false);
            $table->boolean('can_view_all_tickets')->default(true);
            $table->boolean('can_assign_cross_business_tasks')->default(true);
            
            // Status
            $table->enum('status', ['active', 'suspended', 'pending'])->default('active');
            $table->foreignId('invited_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            
            $table->timestamps();
            
            // Indexes and constraints
            $table->unique(['business_group_id', 'business_id']);
            $table->index('business_group_id');
            $table->index('business_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_group_memberships');
    }
};