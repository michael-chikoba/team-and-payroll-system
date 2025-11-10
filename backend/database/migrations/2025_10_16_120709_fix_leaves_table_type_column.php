<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if the table exists
        if (!Schema::hasTable('leaves')) {
            // Create the table if it doesn't exist
            Schema::create('leaves', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->onDelete('cascade');
                $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('type', 50); // Changed from ENUM to VARCHAR
                $table->date('start_date');
                $table->date('end_date');
                $table->integer('total_days');
                $table->text('reason');
                $table->string('status', 50)->default('pending'); // Changed from ENUM to VARCHAR
                $table->text('manager_notes')->nullable();
                $table->string('attachment_path')->nullable();
                $table->timestamps();
                
                // Add indexes for better performance
                $table->index(['employee_id', 'status']);
                $table->index('manager_id');
            });
        } else {
            // If table exists, modify the columns
            // Drop any existing enum constraint
            DB::statement("ALTER TABLE leaves MODIFY COLUMN type VARCHAR(50) NOT NULL");
            DB::statement("ALTER TABLE leaves MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        // Optionally revert to ENUM
        if (Schema::hasTable('leaves')) {
            DB::statement("ALTER TABLE leaves MODIFY COLUMN type ENUM('annual', 'sick', 'maternity', 'paternity', 'bereavement', 'unpaid') NOT NULL");
            DB::statement("ALTER TABLE leaves MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};