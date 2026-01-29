<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('slack_integrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('workspace_name')->nullable();
            $table->string('workspace_id')->nullable();
            $table->string('channel_id');
            $table->string('channel_name');
            $table->text('access_token')->nullable();
            $table->text('webhook_url')->nullable();
            $table->json('notification_settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('connected_at')->nullable();
            $table->unsignedBigInteger('connected_by')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('connected_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('slack_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('slack_integration_id');
            $table->string('notification_type'); // created, approved, status_changed, etc.
            $table->string('status'); // sent, failed
            $table->text('message')->nullable();
            $table->text('error_message')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('slack_integration_id')->references('id')->on('slack_integrations')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('slack_notification_logs');
        Schema::dropIfExists('slack_integrations');
    }
};