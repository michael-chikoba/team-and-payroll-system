<?php
// database/migrations/xxxx_xx_xx_add_ticket_types.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('type', ['issue', 'request', 'change_request'])->default('issue')->after('id');
            $table->string('category')->nullable()->after('type');
            $table->string('subcategory')->nullable()->after('category');
            $table->foreignId('department_id')->nullable()->after('approver_id')->constrained('departments')->nullOnDelete();
            $table->json('attachments')->nullable()->after('comments');
            $table->timestamp('resolved_at')->nullable()->after('approved_at');
            $table->timestamp('closed_at')->nullable()->after('resolved_at');
            $table->decimal('estimated_hours', 8, 2)->nullable()->after('due_date');
            $table->decimal('actual_hours', 8, 2)->nullable()->after('estimated_hours');
            $table->text('resolution_notes')->nullable()->after('comments');
        });
    }

    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'type', 'category', 'subcategory', 'department_id',
                'attachments', 'resolved_at', 'closed_at',
                'estimated_hours', 'actual_hours', 'resolution_notes'
            ]);
        });
    }
};