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
        Schema::table('user_notifications', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('user_notifications', 'title')) {
                $table->string('title')->nullable()->after('type');
            }
            
            if (!Schema::hasColumn('user_notifications', 'action')) {
                $table->string('action')->nullable()->after('message');
            }
            
            if (!Schema::hasColumn('user_notifications', 'data')) {
                $table->json('data')->nullable()->after('action');
            }
            
            if (!Schema::hasColumn('user_notifications', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('is_read');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropColumn(['title', 'action', 'data', 'read_at']);
        });
    }
};