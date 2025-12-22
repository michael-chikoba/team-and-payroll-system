<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
    {
        // 1. Fix: Create departments table if it doesn't exist
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedBigInteger('business_id')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 2. Create shifts table if it doesn't exist
        if (!Schema::hasTable('shifts')) {
            Schema::create('shifts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('business_id')->nullable();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->date('date');
                $table->time('clock_in');
                $table->time('clock_out')->nullable();
                $table->decimal('hours_worked', 5, 2)->nullable();
                $table->enum('shift_type', ['day', 'night', 'evening', 'morning'])->nullable();
                $table->text('notes')->nullable();
                $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
                $table->timestamps();
                
                // Foreign keys
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->foreign('business_id')->references('id')->on('businesses')->onDelete('set null');
                // We verify departments exists above, so this is safe now
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
                
                // Indexes
                $table->index('date');
                $table->index(['employee_id', 'date']);
                $table->index('status');
            });
        }

        // 3. Add assignment fields to shifts if they don't exist
        if (Schema::hasTable('shifts') && !Schema::hasColumn('shifts', 'is_assigned')) {
            Schema::table('shifts', function (Blueprint $table) {
                $table->boolean('is_assigned')->default(false)->after('shift_type');
                $table->unsignedBigInteger('assigned_by')->nullable()->after('is_assigned');
                $table->timestamp('assigned_at')->nullable()->after('assigned_by');
                $table->date('shift_date')->nullable()->after('assigned_at');
                $table->time('assigned_start_time')->nullable()->after('shift_date');
                $table->time('assigned_end_time')->nullable()->after('assigned_start_time');
                $table->text('assignment_notes')->nullable()->after('assigned_end_time');
                $table->enum('assignment_status', ['pending', 'accepted', 'rejected', 'completed'])->default('pending')->after('assignment_notes');
                
                $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
                
                $table->index('shift_date');
                $table->index('assignment_status');
                $table->index('is_assigned');
            });
        }
        
        // 4. Create shift_assignments table
        if (!Schema::hasTable('shift_assignments')) {
            Schema::create('shift_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('assigned_by');
                $table->unsignedBigInteger('business_id')->nullable();
                $table->unsignedBigInteger('country_id')->nullable();
                $table->unsignedBigInteger('department_id')->nullable();
                
                $table->date('shift_date');
                $table->enum('shift_type', ['day', 'night', 'evening', 'morning']);
                $table->time('start_time');
                $table->time('end_time');
                
                $table->text('notes')->nullable();
                $table->enum('status', ['pending', 'accepted', 'rejected', 'completed', 'cancelled'])->default('pending');
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->text('rejection_reason')->nullable();
                
                $table->unsignedBigInteger('shift_id')->nullable();
                
                $table->timestamps();
                
                // Foreign keys
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->foreign('assigned_by')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('business_id')->references('id')->on('businesses')->onDelete('set null');
                $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
                $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('set null');
                
                $table->index('shift_date');
                $table->index('status');
                $table->index(['employee_id', 'shift_date']);
            });
        }
    }
};