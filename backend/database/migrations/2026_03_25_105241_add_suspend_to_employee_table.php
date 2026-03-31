<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Status: active | suspended | archived
            $table->enum('status', ['active', 'suspended', 'archived'])
                  ->default('active')
                  ->index();

            $table->timestamp('suspended_at')->nullable()->after('status');
            $table->timestamp('archived_at')->nullable()->after('suspended_at');
            $table->timestamp('reinstated_at')->nullable()->after('archived_at');

            $table->unsignedBigInteger('suspended_by')->nullable()->after('reinstated_at');
            $table->unsignedBigInteger('archived_by')->nullable()->after('suspended_by');
            $table->unsignedBigInteger('reinstated_by')->nullable()->after('archived_by');

            $table->string('suspension_reason')->nullable()->after('reinstated_by');
            $table->string('archive_reason')->nullable()->after('suspension_reason');
            $table->text('status_notes')->nullable()->after('archive_reason');

            $table->foreign('suspended_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('archived_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reinstated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Mirror the status on users table so login is blocked
        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_status', ['active', 'suspended', 'archived'])
                  ->default('active')
                  ->after('role')
                  ->index();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['suspended_by']);
            $table->dropForeign(['archived_by']);
            $table->dropForeign(['reinstated_by']);
            $table->dropColumn([
                'status', 'suspended_at', 'archived_at', 'reinstated_at',
                'suspended_by', 'archived_by', 'reinstated_by',
                'suspension_reason', 'archive_reason', 'status_notes',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('account_status');
        });
    }
};