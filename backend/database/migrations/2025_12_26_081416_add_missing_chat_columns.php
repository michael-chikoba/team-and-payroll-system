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
        // Add missing columns to chat_groups table
        if (Schema::hasTable('chat_groups')) {
            Schema::table('chat_groups', function (Blueprint $table) {
                if (!Schema::hasColumn('chat_groups', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('avatar');
                }
                if (!Schema::hasColumn('chat_groups', 'last_message_id')) {
                    $table->foreignId('last_message_id')->nullable()->after('is_active');
                }
            });
        }

        // Add missing columns to chat_messages table
        if (Schema::hasTable('chat_messages')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('chat_messages', 'type')) {
                    $table->string('type')->default('text')->after('message');
                }
                if (!Schema::hasColumn('chat_messages', 'reply_to_message_id')) {
                    $table->foreignId('reply_to_message_id')->nullable()->after('type');
                }
                if (!Schema::hasColumn('chat_messages', 'attachment_url')) {
                    $table->string('attachment_url')->nullable()->after('reply_to_message_id');
                }
                if (!Schema::hasColumn('chat_messages', 'attachment_name')) {
                    $table->string('attachment_name')->nullable()->after('attachment_url');
                }
                if (!Schema::hasColumn('chat_messages', 'attachment_size')) {
                    $table->integer('attachment_size')->nullable()->after('attachment_name');
                }
                if (!Schema::hasColumn('chat_messages', 'is_deleted')) {
                    $table->boolean('is_deleted')->default(false)->after('is_edited');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('chat_groups')) {
            Schema::table('chat_groups', function (Blueprint $table) {
                $table->dropColumn(['is_active', 'last_message_id']);
            });
        }

        if (Schema::hasTable('chat_messages')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->dropColumn([
                    'type',
                    'reply_to_message_id',
                    'attachment_url',
                    'attachment_name',
                    'attachment_size',
                    'is_deleted'
                ]);
            });
        }
    }
};