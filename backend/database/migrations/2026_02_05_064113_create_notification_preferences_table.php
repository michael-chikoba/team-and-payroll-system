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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Global settings
            $table->boolean('push_enabled')->default(true);
            $table->boolean('email_enabled')->default(true);
            
            // Notification type preferences (push)
            $table->boolean('push_business_group_invitation')->default(true);
            $table->boolean('push_task_assigned')->default(true);
            $table->boolean('push_schedule_updated')->default(true);
            $table->boolean('push_leave_request')->default(true);
            $table->boolean('push_ticket_created')->default(true);
            $table->boolean('push_reminder')->default(true);
            $table->boolean('push_system_announcement')->default(true);
            
            // Email notification preferences
            $table->boolean('email_business_group_invitation')->default(true);
            $table->boolean('email_task_assigned')->default(true);
            $table->boolean('email_schedule_updated')->default(false);
            $table->boolean('email_leave_request')->default(true);
            $table->boolean('email_ticket_created')->default(false);
            $table->boolean('email_reminder')->default(true);
            $table->boolean('email_system_announcement')->default(true);
            
            // Quiet hours
            $table->boolean('quiet_hours_enabled')->default(false);
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};