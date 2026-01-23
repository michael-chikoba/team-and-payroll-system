<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add channels support to chat_groups (with checks for existing columns)
        Schema::table('chat_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_groups', 'is_channel')) {
                $table->boolean('is_channel')->default(false)->after('type');
            }
            if (!Schema::hasColumn('chat_groups', 'is_private')) {
                $table->boolean('is_private')->default(false)->after('is_channel');
            }
            if (!Schema::hasColumn('chat_groups', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_private');
            }
            if (!Schema::hasColumn('chat_groups', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('chat_groups', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('is_archived');
            }
            if (!Schema::hasColumn('chat_groups', 'last_message_id')) {
                $table->integer('last_message_id')->unsigned()->nullable()->after('description');
            }
            if (!Schema::hasColumn('chat_groups', 'channel_prefix')) {
                $table->string('channel_prefix', 10)->nullable()->after('name');
            }
            if (!Schema::hasColumn('chat_groups', 'settings')) {
                $table->json('settings')->nullable()->after('description');
            }
        });

        // 2. Create chat_reactions table
        if (!Schema::hasTable('chat_reactions')) {
            Schema::create('chat_reactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chat_message_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('emoji', 50);
                $table->timestamps();
                
                $table->unique(['chat_message_id', 'user_id', 'emoji']);
                $table->index(['chat_message_id', 'emoji']);
            });
        }

        // 3. Create chat_threads table
        if (!Schema::hasTable('chat_threads')) {
            Schema::create('chat_threads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chat_group_id')->constrained()->onDelete('cascade');
                $table->foreignId('parent_message_id')->constrained('chat_messages')->onDelete('cascade');
                $table->foreignId('last_reply_id')->nullable()->constrained('chat_messages')->onDelete('set null');
                $table->integer('reply_count')->default(0);
                $table->timestamp('last_reply_at')->nullable();
                $table->timestamps();
                
                $table->unique(['chat_group_id', 'parent_message_id']);
            });
        }

        // 4. Add thread support to chat_messages
        Schema::table('chat_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_messages', 'thread_id')) {
                $table->foreignId('thread_id')->nullable()->after('parent_id')->constrained('chat_threads')->onDelete('cascade');
            }
            if (!Schema::hasColumn('chat_messages', 'is_thread_reply')) {
                $table->boolean('is_thread_reply')->default(false)->after('thread_id');
            }
            if (!Schema::hasColumn('chat_messages', 'mentions')) {
                $table->json('mentions')->nullable()->after('message');
            }
            if (!Schema::hasColumn('chat_messages', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('is_edited');
            }
            if (!Schema::hasColumn('chat_messages', 'pinned_at')) {
                $table->timestamp('pinned_at')->nullable()->after('is_pinned');
            }
            if (!Schema::hasColumn('chat_messages', 'pinned_by')) {
                $table->foreignId('pinned_by')->nullable()->after('pinned_at')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('chat_messages', 'type')) {
                $table->string('type')->default('text')->after('message');
            }
            if (!Schema::hasColumn('chat_messages', 'attachment_url')) {
                $table->string('attachment_url')->nullable()->after('attachments');
            }
            if (!Schema::hasColumn('chat_messages', 'attachment_name')) {
                $table->string('attachment_name')->nullable()->after('attachment_url');
            }
            if (!Schema::hasColumn('chat_messages', 'attachment_size')) {
                $table->bigInteger('attachment_size')->nullable()->after('attachment_name');
            }
            if (!Schema::hasColumn('chat_messages', 'is_deleted')) {
                $table->boolean('is_deleted')->default(false)->after('is_edited');
            }
            // Rename parent_id to reply_to_message_id for consistency
            if (Schema::hasColumn('chat_messages', 'parent_id') && !Schema::hasColumn('chat_messages', 'reply_to_message_id')) {
                $table->renameColumn('parent_id', 'reply_to_message_id');
            }
        });

        // 5. Create chat_bookmarks table
        if (!Schema::hasTable('chat_bookmarks')) {
            Schema::create('chat_bookmarks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('chat_message_id')->constrained()->onDelete('cascade');
                $table->string('note')->nullable();
                $table->timestamps();
                
                $table->unique(['user_id', 'chat_message_id']);
            });
        }

        // 6. Create chat_typing_indicators table
        if (!Schema::hasTable('chat_typing_indicators')) {
            Schema::create('chat_typing_indicators', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chat_group_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamp('last_typed_at');
                $table->timestamps();
                
                $table->unique(['chat_group_id', 'user_id']);
            });
        }

        // 7. Enhanced chat_group_members
        Schema::table('chat_group_members', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_group_members', 'joined_at')) {
                $table->timestamp('joined_at')->nullable()->after('created_at');
            }
            if (!Schema::hasColumn('chat_group_members', 'invited_by')) {
                $table->foreignId('invited_by')->nullable()->after('user_id')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('chat_group_members', 'notification_preferences')) {
                $table->json('notification_preferences')->nullable()->after('muted_until');
            }
            if (!Schema::hasColumn('chat_group_members', 'is_favorite')) {
                $table->boolean('is_favorite')->default(false)->after('is_muted');
            }
        });

        // 8. Create chat_invitations table
        if (!Schema::hasTable('chat_invitations')) {
            Schema::create('chat_invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chat_group_id')->constrained()->onDelete('cascade');
                $table->foreignId('invited_user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('invited_by')->constrained('users')->onDelete('cascade');
                $table->string('status')->default('pending');
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('declined_at')->nullable();
                $table->timestamps();
                
                $table->index(['chat_group_id', 'status']);
            });
        }

        // 9. Create chat_files table
        if (!Schema::hasTable('chat_files')) {
            Schema::create('chat_files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chat_group_id')->constrained()->onDelete('cascade');
                $table->foreignId('chat_message_id')->constrained()->onDelete('cascade');
                $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
                $table->string('filename');
                $table->string('original_filename');
                $table->string('mime_type');
                $table->bigInteger('size');
                $table->string('path');
                $table->string('thumbnail_path')->nullable();
                $table->timestamps();
                
                $table->index(['chat_group_id', 'mime_type']);
            });
        }

        // 10. Create chat_mentions table
        if (!Schema::hasTable('chat_mentions')) {
            Schema::create('chat_mentions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chat_message_id')->constrained()->onDelete('cascade');
                $table->foreignId('mentioned_user_id')->constrained('users')->onDelete('cascade');
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                
                $table->unique(['chat_message_id', 'mentioned_user_id']);
                $table->index(['mentioned_user_id', 'is_read']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_mentions');
        Schema::dropIfExists('chat_files');
        Schema::dropIfExists('chat_invitations');
        
        Schema::table('chat_group_members', function (Blueprint $table) {
            if (Schema::hasColumn('chat_group_members', 'joined_at')) {
                $table->dropColumn('joined_at');
            }
            if (Schema::hasColumn('chat_group_members', 'invited_by')) {
                $table->dropForeign(['invited_by']);
                $table->dropColumn('invited_by');
            }
            if (Schema::hasColumn('chat_group_members', 'notification_preferences')) {
                $table->dropColumn('notification_preferences');
            }
            if (Schema::hasColumn('chat_group_members', 'is_favorite')) {
                $table->dropColumn('is_favorite');
            }
        });
        
        Schema::dropIfExists('chat_typing_indicators');
        Schema::dropIfExists('chat_bookmarks');
        
        Schema::table('chat_messages', function (Blueprint $table) {
            $columns = ['thread_id', 'is_thread_reply', 'mentions', 'is_pinned', 'pinned_at', 'pinned_by', 'type', 'attachment_url', 'attachment_name', 'attachment_size', 'is_deleted'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('chat_messages', $column)) {
                    if (in_array($column, ['thread_id', 'pinned_by'])) {
                        $table->dropForeign([$column]);
                    }
                    $table->dropColumn($column);
                }
            }
        });
        
        Schema::dropIfExists('chat_threads');
        Schema::dropIfExists('chat_reactions');
        
        Schema::table('chat_groups', function (Blueprint $table) {
            $columns = ['is_channel', 'is_private', 'is_active', 'is_archived', 'archived_at', 'last_message_id', 'channel_prefix', 'settings'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('chat_groups', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};