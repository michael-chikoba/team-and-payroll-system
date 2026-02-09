<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductivityMetricsTables extends Migration
{
    public function up()
    {
        // SLA Logs table
        if (!Schema::hasTable('sla_logs')) {
            Schema::create('sla_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
                $table->string('sla_type'); // response_time, resolution_time
                $table->integer('target_hours'); // 24, 72, etc.
                $table->integer('actual_hours');
                $table->boolean('is_met');
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // Task Completion Logs table
        if (!Schema::hasTable('task_completion_logs')) {
            Schema::create('task_completion_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamp('completed_at');
                $table->timestamp('deadline');
                $table->integer('completion_time_hours');
                $table->boolean('is_on_time');
                $table->decimal('quality_score', 5, 2)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // Weekly Performance Snapshot table
        if (!Schema::hasTable('weekly_performance_snapshots')) {
            Schema::create('weekly_performance_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->date('week_start');
                $table->date('week_end');
                $table->integer('tasks_completed');
                $table->integer('tasks_on_time');
                $table->integer('tickets_handled');
                $table->integer('sla_met');
                $table->decimal('productivity_score', 5, 2);
                $table->decimal('sla_compliance_rate', 5, 2);
                $table->decimal('on_time_rate', 5, 2);
                $table->decimal('efficiency_rate', 5, 2);
                $table->timestamps();
                
                $table->index(['user_id', 'week_start']);
            });
        }

        // Add SLA tracking columns to tickets table (with checks)
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'first_response_at')) {
                $table->timestamp('first_response_at')->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('tickets', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('first_response_at');
            }
            
            if (!Schema::hasColumn('tickets', 'response_time_hours')) {
                $table->integer('response_time_hours')->nullable()->after('resolved_at');
            }
            
            if (!Schema::hasColumn('tickets', 'resolution_time_hours')) {
                $table->integer('resolution_time_hours')->nullable()->after('response_time_hours');
            }
            
            if (!Schema::hasColumn('tickets', 'meets_response_sla')) {
                $table->boolean('meets_response_sla')->nullable()->after('resolution_time_hours');
            }
            
            if (!Schema::hasColumn('tickets', 'meets_resolution_sla')) {
                $table->boolean('meets_resolution_sla')->nullable()->after('meets_response_sla');
            }
        });

        // Add completion tracking columns to tasks table (with checks)
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('deadline');
            }
            
            if (!Schema::hasColumn('tasks', 'reopened_at')) {
                $table->timestamp('reopened_at')->nullable()->after('completed_at');
            }
            
            if (!Schema::hasColumn('tasks', 'completion_time_hours')) {
                $table->integer('completion_time_hours')->nullable()->after('reopened_at');
            }
            
            if (!Schema::hasColumn('tasks', 'is_on_time')) {
                $table->boolean('is_on_time')->nullable()->after('completion_time_hours');
            }
            
            if (!Schema::hasColumn('tasks', 'quality_rating')) {
                $table->decimal('quality_rating', 3, 2)->nullable()->after('is_on_time');
            }
        });
    }

    public function down()
    {
        // Drop tables if they exist
        Schema::dropIfExists('sla_logs');
        Schema::dropIfExists('task_completion_logs');
        Schema::dropIfExists('weekly_performance_snapshots');
        
        // Safely drop columns from tickets table
        Schema::table('tickets', function (Blueprint $table) {
            $columnsToDrop = [
                'first_response_at',
                'resolved_at',
                'response_time_hours',
                'resolution_time_hours',
                'meets_response_sla',
                'meets_resolution_sla'
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('tickets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
        
        // Safely drop columns from tasks table
        Schema::table('tasks', function (Blueprint $table) {
            $columnsToDrop = [
                'completed_at',
                'reopened_at',
                'completion_time_hours',
                'is_on_time',
                'quality_rating'
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('tasks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}