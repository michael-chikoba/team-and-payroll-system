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
        Schema::create('chat_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_group_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name'); // e.g., "GitHub Notifications", "CI/CD Bot"
            $table->text('description')->nullable();
            $table->string('api_key', 64)->unique(); // Unique API key for this integration
            $table->string('webhook_secret', 64)->nullable(); // Optional webhook secret for verification
            $table->string('icon_url')->nullable(); // Bot avatar/icon
            $table->boolean('is_active')->default(true);
            $table->json('permissions')->nullable(); // What the integration can do
            $table->json('settings')->nullable(); // Custom settings
            $table->integer('message_count')->default(0); // Track usage
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['chat_group_id', 'is_active']);
            $table->index('api_key');
        });

        // Table to log integration activities
        Schema::create('chat_integration_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_integration_id')->constrained()->onDelete('cascade');
            $table->string('action'); // 'message_sent', 'error', 'rate_limit'
            $table->text('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('status'); // 'success', 'failed'
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['chat_integration_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_integration_logs');
        Schema::dropIfExists('chat_integrations');
    }
};