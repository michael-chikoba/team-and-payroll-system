<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_supervisor_messages_table
 *
 * Stores private messages between an employee and their direct supervisor.
 * Conversations are thread-keyed on (employee_id, supervisor_user_id) so
 * every employee/supervisor pair has exactly one isolated thread.
 *
 * Run:  php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_messages', function (Blueprint $table) {
            $table->id();

            // Thread participants
            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->cascadeOnDelete()
                  ->comment('The employee side of the conversation');

            $table->foreignId('supervisor_user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('The supervisor (manager/admin) side of the conversation');

            // Who actually sent this particular message
            $table->foreignId('sender_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('Could be the employee OR the supervisor');

            // Content
            $table->text('message');
            $table->string('category', 30)->nullable()
                  ->comment('leave|performance|payroll|workload|schedule|general|other');

            // Delivery / read tracking
            $table->timestamp('read_at')->nullable()
                  ->comment('NULL = unread; set when the recipient fetches the thread');

            $table->timestamps();
            $table->softDeletes(); // allows "unsend" within 5-minute window

            // ── Indexes ──────────────────────────────────────────────
            // Primary lookup: fetch the full thread for a pair
            $table->index(['employee_id', 'supervisor_user_id', 'created_at'],
                          'idx_sup_msg_thread');

            // Unread-count queries (WHERE read_at IS NULL AND sender_id != ?)
            $table->index(['employee_id', 'supervisor_user_id', 'sender_id', 'read_at'],
                          'idx_sup_msg_unread');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_messages');
    }
};