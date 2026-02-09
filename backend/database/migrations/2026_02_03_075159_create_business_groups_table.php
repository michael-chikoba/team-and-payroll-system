<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('group_type', ['holding', 'franchise', 'subsidiary', 'partnership'])
                  ->default('holding');
            $table->foreignId('parent_group_id')
                  ->nullable()
                  ->constrained('business_groups')
                  ->nullOnDelete();
            
            // Feature Settings
            $table->boolean('allow_cross_business_tickets')->default(true);
            $table->boolean('allow_cross_business_tasks')->default(true);
            $table->boolean('allow_cross_business_projects')->default(false);
            $table->boolean('allow_employee_visibility')->default(false);
            $table->boolean('allow_resource_sharing')->default(false);
            
            // Metadata
            $table->foreignId('created_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('parent_group_id');
            $table->index('status');
            $table->index('created_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_groups');
    }
};