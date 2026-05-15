<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === COURSES ===
        Schema::create('learning_courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable()->index();
            $table->string('thumbnail')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->index();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('business_id');
            $table->json('assigned_departments')->nullable(); // e.g. ["Finance", "HR"]
            $table->json('assigned_roles')->nullable();       // e.g. ["employee", "manager"]
            $table->boolean('allow_self_enroll')->default(true);
            $table->integer('estimated_minutes')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();

            $table->index(['business_id', 'status']);
        });

        // === MODULES ===
        Schema::create('learning_modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['video', 'pdf', 'text', 'link', 'quiz', 'scorm']);
            $table->text('content');
            $table->integer('order')->default(0);
            $table->integer('duration_minutes')->nullable();
            $table->timestamps();

            $table->foreign('course_id')
                  ->references('id')
                  ->on('learning_courses')
                  ->cascadeOnDelete();

            $table->index(['course_id', 'order']);
        });

        // === ASSESSMENTS (One per course) ===
        Schema::create('learning_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id')->unique();
            $table->string('title');
            $table->integer('pass_mark')->default(70);
            $table->integer('max_attempts')->default(3);
            $table->integer('time_limit_minutes')->nullable();
            $table->timestamps();

            $table->foreign('course_id')
                  ->references('id')
                  ->on('learning_courses')
                  ->cascadeOnDelete();
        });

        // === QUESTIONS ===
        Schema::create('learning_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_id');
            $table->text('question');
            $table->integer('order')->default(0);
            $table->integer('points')->default(1);
            $table->timestamps();

            $table->foreign('assessment_id')
                  ->references('id')
                  ->on('learning_assessments')
                  ->cascadeOnDelete();

            $table->index(['assessment_id', 'order']);
        });

        // === QUESTION OPTIONS ===
        Schema::create('learning_question_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->string('option_text');
            $table->boolean('is_correct')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->foreign('question_id')
                  ->references('id')
                  ->on('learning_questions')
                  ->cascadeOnDelete();

            $table->index(['question_id', 'order']);
        });

        // === ENROLLMENTS ===
        Schema::create('learning_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('employee_id');
            $table->enum('status', ['enrolled', 'in_progress', 'completed', 'failed'])->default('enrolled')->index();
            $table->integer('progress_percent')->default(0);
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('enrolled_by')->nullable(); // null = self-enrolled

            $table->timestamps();

            $table->unique(['course_id', 'employee_id']);
            $table->index(['employee_id', 'status']);
            $table->index(['course_id', 'status']);

            $table->foreign('course_id')
                  ->references('id')
                  ->on('learning_courses')
                  ->cascadeOnDelete();

            $table->foreign('employee_id')
                  ->references('id')
                  ->on('employees')
                  ->cascadeOnDelete();

            $table->foreign('enrolled_by')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });

        // === MODULE PROGRESS ===
        Schema::create('learning_module_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enrollment_id');
            $table->unsignedBigInteger('module_id');
            $table->boolean('completed')->default(false)->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['enrollment_id', 'module_id']);

            $table->foreign('enrollment_id')
                  ->references('id')
                  ->on('learning_enrollments')
                  ->cascadeOnDelete();

            $table->foreign('module_id')
                  ->references('id')
                  ->on('learning_modules')
                  ->cascadeOnDelete();
        });

        // === ASSESSMENT ATTEMPTS ===
        Schema::create('learning_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enrollment_id');
            $table->unsignedBigInteger('assessment_id');
            $table->integer('score')->nullable();
            $table->boolean('passed')->nullable();
            $table->json('answers')->nullable(); // [{question_id, selected_option_id, ...}]
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('enrollment_id')
                  ->references('id')
                  ->on('learning_enrollments')
                  ->cascadeOnDelete();

            $table->foreign('assessment_id')
                  ->references('id')
                  ->on('learning_assessments')
                  ->cascadeOnDelete();

            $table->index(['enrollment_id', 'assessment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_attempts');
        Schema::dropIfExists('learning_module_progress');
        Schema::dropIfExists('learning_enrollments');
        Schema::dropIfExists('learning_question_options');
        Schema::dropIfExists('learning_questions');
        Schema::dropIfExists('learning_assessments');
        Schema::dropIfExists('learning_modules');
        Schema::dropIfExists('learning_courses');
    }
};