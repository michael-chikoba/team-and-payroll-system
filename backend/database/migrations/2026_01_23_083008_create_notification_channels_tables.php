<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create notification_channels table
        if (!Schema::hasTable('notification_channels')) {
            Schema::create('notification_channels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->onDelete('cascade');
                $table->string('name'); // e.g., "IT Support Tickets"
                $table->enum('channel_type', ['chat_group', 'email', 'slack', 'webhook'])->default('chat_group');
                
                // Channel-specific configuration
                $table->foreignId('chat_group_id')->nullable()->constrained()->onDelete('set null');
                $table->string('email_address')->nullable();
                $table->string('slack_webhook_url')->nullable();
                $table->string('webhook_url')->nullable();
                
                // Event subscriptions
                $table->json('subscribed_events')->nullable(); // ['ticket.created', 'ticket.comment', 'ticket.attachment']
                
                // Filters
                $table->json('filters')->nullable(); // {ticket_types: ['issue'], priorities: ['high', 'critical']}
                
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->constrained('users');
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['business_id', 'is_active']);
                $table->index('channel_type');
            });
        } else {
            // Update existing table if needed
            $this->updateNotificationChannelsTable();
        }

        // 2. Create notification_logs table for tracking
        if (!Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('notification_channel_id')->constrained()->onDelete('cascade');
                $table->foreignId('ticket_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('event_type'); // 'ticket.created', 'ticket.comment', etc.
                $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
                $table->json('payload')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
                
                $table->index(['notification_channel_id', 'status']);
                $table->index(['ticket_id', 'event_type']);
            });
        } else {
            // Update existing table if needed
            $this->updateNotificationLogsTable();
        }

        // 3. Create ticket_notification_settings table (per-ticket overrides)
        if (!Schema::hasTable('ticket_notification_settings')) {
            Schema::create('ticket_notification_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
                $table->foreignId('notification_channel_id')->constrained()->onDelete('cascade');
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();
                
                // ✅ FIXED: Shortened unique constraint name
                $table->unique(['ticket_id', 'notification_channel_id'], 'ticket_notif_channel_unique');
            });
        } else {
            // Update existing table if needed
            $this->updateTicketNotificationSettingsTable();
        }

        // 4. Seed default notification channels for existing businesses
        if (Schema::hasTable('businesses')) {
            $this->seedDefaultChannels();
        }
    }

    /**
     * Update existing notification_channels table with missing columns
     */
    protected function updateNotificationChannelsTable(): void
    {
        // Helper function to check if index exists
        $hasIndex = function($tableName, $indexName) {
            try {
                $result = DB::select("SHOW INDEX FROM $tableName WHERE Key_name = ?", [$indexName]);
                return count($result) > 0;
            } catch (\Exception $e) {
                return false;
            }
        };

        Schema::table('notification_channels', function (Blueprint $table) use ($hasIndex) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('notification_channels', 'filters')) {
                $table->json('filters')->nullable()->after('subscribed_events');
            }
            
            if (!Schema::hasColumn('notification_channels', 'channel_type')) {
                $table->enum('channel_type', ['chat_group', 'email', 'slack', 'webhook'])
                      ->default('chat_group')
                      ->after('name');
            }
            
            if (!Schema::hasColumn('notification_channels', 'email_address')) {
                $table->string('email_address')->nullable()->after('chat_group_id');
            }
            
            if (!Schema::hasColumn('notification_channels', 'slack_webhook_url')) {
                $table->string('slack_webhook_url')->nullable()->after('email_address');
            }
            
            if (!Schema::hasColumn('notification_channels', 'webhook_url')) {
                $table->string('webhook_url')->nullable()->after('slack_webhook_url');
            }
            
            if (!Schema::hasColumn('notification_channels', 'subscribed_events')) {
                $table->json('subscribed_events')->nullable()->after('webhook_url');
            }
            
            if (!Schema::hasColumn('notification_channels', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('filters');
            }
            
            if (!Schema::hasColumn('notification_channels', 'deleted_at')) {
                $table->softDeletes();
            }
            
            // Check and add indexes if they don't exist
            if (!$hasIndex('notification_channels', 'notification_channels_business_id_is_active_index')) {
                $table->index(['business_id', 'is_active']);
            }
            
            if (!$hasIndex('notification_channels', 'notification_channels_channel_type_index')) {
                $table->index('channel_type');
            }
        });
    }

    /**
     * Update existing notification_logs table with missing columns
     */
    protected function updateNotificationLogsTable(): void
    {
        // Helper function to check if index exists
        $hasIndex = function($tableName, $indexName) {
            try {
                $result = DB::select("SHOW INDEX FROM $tableName WHERE Key_name = ?", [$indexName]);
                return count($result) > 0;
            } catch (\Exception $e) {
                return false;
            }
        };

        Schema::table('notification_logs', function (Blueprint $table) use ($hasIndex) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('notification_logs', 'event_type')) {
                $table->string('event_type')->after('ticket_id');
            }
            
            if (!Schema::hasColumn('notification_logs', 'status')) {
                $table->enum('status', ['pending', 'sent', 'failed'])->default('pending')->after('event_type');
            }
            
            if (!Schema::hasColumn('notification_logs', 'payload')) {
                $table->json('payload')->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('notification_logs', 'error_message')) {
                $table->text('error_message')->nullable()->after('payload');
            }
            
            if (!Schema::hasColumn('notification_logs', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('error_message');
            }
            
            // Check and add indexes if they don't exist
            if (!$hasIndex('notification_logs', 'notification_logs_notification_channel_id_status_index')) {
                $table->index(['notification_channel_id', 'status']);
            }
            
            if (!$hasIndex('notification_logs', 'notification_logs_ticket_id_event_type_index')) {
                $table->index(['ticket_id', 'event_type']);
            }
        });
    }

    /**
     * Update existing ticket_notification_settings table with missing columns
     */
    protected function updateTicketNotificationSettingsTable(): void
    {
        // Helper function to check if index exists
        $hasIndex = function($tableName, $indexName) {
            try {
                $result = DB::select("SHOW INDEX FROM $tableName WHERE Key_name = ?", [$indexName]);
                return count($result) > 0;
            } catch (\Exception $e) {
                return false;
            }
        };

        Schema::table('ticket_notification_settings', function (Blueprint $table) use ($hasIndex) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('ticket_notification_settings', 'is_enabled')) {
                $table->boolean('is_enabled')->default(true)->after('notification_channel_id');
            }
            
            // Check and add unique constraint if it doesn't exist
            if (!$hasIndex('ticket_notification_settings', 'ticket_notif_channel_unique')) {
                $table->unique(['ticket_id', 'notification_channel_id'], 'ticket_notif_channel_unique');
            }
        });
    }

    /**
     * Seed default notification channels for existing businesses
     */
    protected function seedDefaultChannels(): void
    {
        // Check if there are already notification channels to avoid duplicate seeding
        if (Schema::hasTable('notification_channels') && DB::table('notification_channels')->count() > 0) {
            return;
        }

        if (!Schema::hasTable('businesses')) {
            return;
        }

        $businesses = DB::table('businesses')->get();
        
        foreach ($businesses as $business) {
            // Try to find a general channel for this business
            $generalChannel = DB::table('chat_groups')
                ->where('business_id', $business->id)
                ->where(function($query) {
                    $query->where('name', 'like', '%general%')
                          ->orWhere('name', 'like', '%announcements%');
                })
                ->first();

            if ($generalChannel) {
                // Check if a notification channel already exists for this chat group
                $existingChannel = DB::table('notification_channels')
                    ->where('business_id', $business->id)
                    ->where('chat_group_id', $generalChannel->id)
                    ->first();

                if (!$existingChannel) {
                    DB::table('notification_channels')->insert([
                        'business_id' => $business->id,
                        'name' => 'Ticket Notifications',
                        'channel_type' => 'chat_group',
                        'chat_group_id' => $generalChannel->id,
                        'subscribed_events' => json_encode([
                            'ticket.created',
                            'ticket.comment_added',
                            'ticket.attachment_uploaded'
                        ]),
                        'filters' => json_encode([]),
                        'is_active' => true,
                        'created_by' => 1, // Assuming user ID 1 exists
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_notification_settings');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_channels');
    }
};