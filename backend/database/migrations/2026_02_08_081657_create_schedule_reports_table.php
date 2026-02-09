<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('schedule_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('submitted_by')->constrained('users')->onDelete('cascade');
            
            // Report Content - either text or file
            $table->text('report_content')->nullable(); // Now nullable since they might upload a file instead
            $table->string('report_type')->default('text'); // 'text', 'file', or 'both'
            
            // File Upload Fields
            $table->string('file_path')->nullable(); // Path to uploaded file
            $table->string('file_name')->nullable(); // Original file name
            $table->string('file_type')->nullable(); // MIME type (application/pdf, application/msword, etc.)
            $table->integer('file_size')->nullable(); // File size in bytes
            
            $table->json('metadata')->nullable(); // For additional data like hours worked, tasks completed, etc.
            $table->string('status')->default('submitted'); // submitted, reviewed, approved, rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comments')->nullable();
            $table->timestamps();
            
            // Indexes for faster filtering
            $table->index(['employee_id', 'created_at']);
            $table->index(['schedule_id', 'status']);
            $table->index('status');
            $table->index('report_type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedule_reports');
    }
};