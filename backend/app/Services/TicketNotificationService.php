<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketAttachment;
use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TicketNotificationService
{

    protected $slackService;

     public function __construct(SlackService $slackService)
    {
        $this->slackService = $slackService;
    }
    /**
     * Send notification for ticket created event
     */
    public function notifyTicketCreated(Ticket $ticket,): void
    {

    $this->slackService->sendTicketNotification($ticket, 'created');
        $this->sendNotification($ticket, 'ticket.created', [
            'title' => '🎫 New Ticket Created',
            'message' => $this->formatTicketCreatedMessage($ticket),
            'ticket' => $this->formatTicketData($ticket),
        ]);
    }

    /**
     * Send notification for comment added event
     */
    public function notifyCommentAdded(Ticket $ticket, TicketComment $comment): void
    {
         $this->slackService->sendTicketNotification($ticket, 'comment_added', [
                'comment' => $comment->content,
                'commenter' => $comment->user->name ?? 'Unknown'
            ]);
        $this->sendNotification($ticket, 'ticket.comment_added', [
            'title' => '💬 New Comment on Ticket #' . $ticket->id,
            'message' => $this->formatCommentMessage($ticket, $comment),
            'ticket' => $this->formatTicketData($ticket),
            'comment' => [
                'id' => $comment->id,
                'content' => $comment->content,
                'user' => $comment->user->name,
                'is_internal' => $comment->is_internal,
            ],
        ]);
    }

    /**
     * Send notification for attachment uploaded event
     */
    public function notifyAttachmentUploaded(Ticket $ticket, TicketAttachment $attachment): void
    {
         // Send Slack notification
            $this->slackService->sendTicketNotification($ticket, 'attachment_uploaded', [
                'filename' => $attachment->original_name,
                'uploader' => $attachment->user->name ?? 'Unknown'
            ]);
        $this->sendNotification($ticket, 'ticket.attachment_uploaded', [
            'title' => '📎 New Attachment on Ticket #' . $ticket->id,
            'message' => $this->formatAttachmentMessage($ticket, $attachment),
            'ticket' => $this->formatTicketData($ticket),
            'attachment' => [
                'id' => $attachment->id,
                'original_name' => $attachment->original_name,
                'size' => $attachment->size,
                'mime_type' => $attachment->mime_type,
                'user' => $attachment->user->name,
            ],
        ]);
    }

    /**
     * Send notification for status changed event
     */
    public function notifyStatusChanged(Ticket $ticket, string $oldStatus, string $newStatus): void
    {

     $this->slackService->sendTicketNotification($ticket, 'status_changed', [
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);

        $this->sendNotification($ticket, 'ticket.status_changed', [
            'title' => '🔄 Ticket Status Changed',
            'message' => $this->formatStatusChangeMessage($ticket, $oldStatus, $newStatus),
            'ticket' => $this->formatTicketData($ticket),
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);
    }
    
   


    /**
     * Send notification for ticket approved event
     */
    public function notifyTicketApproved(Ticket $ticket): void
    {
        $this->sendNotification($ticket, 'ticket.approved', [
            'title' => '✅ Ticket Approved',
            'message' => $this->formatApprovalMessage($ticket),
            'ticket' => $this->formatTicketData($ticket),
        ]);
    }

    /**
     * Core notification sending logic
     */
    protected function sendNotification(Ticket $ticket, string $eventType, array $data): void
    {
        try {
            // Get user's business
            $businessId = $ticket->user->employee->business_id ?? null;
            
            if (!$businessId) {
                Log::warning('Cannot send notification: ticket user has no business', [
                    'ticket_id' => $ticket->id,
                    'event_type' => $eventType,
                ]);
                return;
            }

            // Get all active notification channels for this business and event
            $channels = NotificationChannel::active()
                ->forBusiness($businessId)
                ->subscribedTo($eventType)
                ->get();

            foreach ($channels as $channel) {
                // Check if ticket matches channel filters
                if (!$channel->matchesFilters($ticket)) {
                    continue;
                }

                // Create log entry
                $log = NotificationLog::create([
                    'notification_channel_id' => $channel->id,
                    'ticket_id' => $ticket->id,
                    'event_type' => $eventType,
                    'status' => 'pending',
                    'payload' => $data,
                ]);

                // Send via appropriate channel
                try {
                    switch ($channel->channel_type) {
                        case 'chat_group':
                            $this->sendToChatGroup($channel, $data);
                            break;
                        case 'email':
                            $this->sendToEmail($channel, $data);
                            break;
                        case 'slack':
                            $this->sendToSlack($channel, $data);
                            break;
                        case 'webhook':
                            $this->sendToWebhook($channel, $data);
                            break;
                    }

                    $log->markAsSent();
                    
                } catch (\Exception $e) {
                    Log::error('Failed to send notification', [
                        'channel_id' => $channel->id,
                        'ticket_id' => $ticket->id,
                        'event_type' => $eventType,
                        'error' => $e->getMessage(),
                    ]);
                    
                    $log->markAsFailed($e->getMessage());
                }
            }

        } catch (\Exception $e) {
            Log::error('Notification service error', [
                'ticket_id' => $ticket->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification to chat group
     */
    protected function sendToChatGroup(NotificationChannel $channel, array $data): void
    {
        if (!$channel->chat_group_id) {
            throw new \Exception('No chat group configured for this channel');
        }

        ChatMessage::create([
            'chat_group_id' => $channel->chat_group_id,
            'user_id' => 1, // System user - you may want to create a dedicated system user
            'message' => $data['message'],
            'type' => 'system',
            'meta' => [
                'notification_type' => 'ticket_notification',
                'ticket_id' => $data['ticket']['id'] ?? null,
            ],
        ]);
    }

    /**
     * Send notification to email
     */
    protected function sendToEmail(NotificationChannel $channel, array $data): void
    {
        if (!$channel->email_address) {
            throw new \Exception('No email address configured for this channel');
        }

        // Implement email sending logic here
        // You can use Laravel Mail or a dedicated notification class
        Log::info('Email notification sent', [
            'to' => $channel->email_address,
            'title' => $data['title'],
        ]);
    }

    /**
     * Send notification to Slack
     */
    protected function sendToSlack(NotificationChannel $channel, array $data): void
    {
        if (!$channel->slack_webhook_url) {
            throw new \Exception('No Slack webhook URL configured for this channel');
        }

        $response = Http::post($channel->slack_webhook_url, [
            'text' => $data['title'],
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $data['message'],
                    ],
                ],
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Slack webhook failed: ' . $response->body());
        }
    }

    /**
     * Send notification to webhook
     */
    protected function sendToWebhook(NotificationChannel $channel, array $data): void
    {
        if (!$channel->webhook_url) {
            throw new \Exception('No webhook URL configured for this channel');
        }

        $response = Http::post($channel->webhook_url, $data);

        if (!$response->successful()) {
            throw new \Exception('Webhook failed: ' . $response->body());
        }
    }

    // ===== Message Formatters =====

    protected function formatTicketCreatedMessage(Ticket $ticket): string
    {
        return sprintf(
            "**Ticket #%d: %s**\n\n" .
            "**Type:** %s\n" .
            "**Priority:** %s\n" .
            "**Category:** %s\n" .
            "**Requester:** %s\n\n" .
            "%s",
            $ticket->id,
            $ticket->title,
            ucfirst($ticket->type),
            ucfirst($ticket->priority),
            $ticket->category,
            $ticket->user->name ?? 'Unknown',
            substr($ticket->description, 0, 200) . (strlen($ticket->description) > 200 ? '...' : '')
        );
    }

    protected function formatCommentMessage(Ticket $ticket, TicketComment $comment): string
    {
        return sprintf(
            "**Ticket #%d: %s**\n\n" .
            "**Comment by:** %s\n" .
            "%s%s",
            $ticket->id,
            $ticket->title,
            $comment->user->name ?? 'Unknown',
            $comment->is_internal ? '🔒 *Internal Note*\n' : '',
            substr($comment->content, 0, 300) . (strlen($comment->content) > 300 ? '...' : '')
        );
    }

    protected function formatAttachmentMessage(Ticket $ticket, TicketAttachment $attachment): string
    {
        return sprintf(
            "**Ticket #%d: %s**\n\n" .
            "**Uploaded by:** %s\n" .
            "**File:** %s (%s)\n",
            $ticket->id,
            $ticket->title,
            $attachment->user->name ?? 'Unknown',
            $attachment->original_name,
            $this->formatFileSize($attachment->size)
        );
    }

    protected function formatStatusChangeMessage(Ticket $ticket, string $oldStatus, string $newStatus): string
    {
        return sprintf(
            "**Ticket #%d: %s**\n\n" .
            "**Status changed:** %s → %s\n" .
            "**Updated by:** %s",
            $ticket->id,
            $ticket->title,
            ucfirst($oldStatus),
            ucfirst($newStatus),
            auth()->user()->name ?? 'System'
        );
    }

    protected function formatApprovalMessage(Ticket $ticket): string
    {
        return sprintf(
            "**Ticket #%d Approved! ✅**\n\n" .
            "**Title:** %s\n" .
            "**Approved by:** %s\n" .
            "**Priority:** %s",
            $ticket->id,
            $ticket->title,
            $ticket->approver->name ?? 'Unknown',
            ucfirst($ticket->priority)
        );
    }

    protected function formatTicketData(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'title' => $ticket->title,
            'type' => $ticket->type,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'category' => $ticket->category,
            'subcategory' => $ticket->subcategory,
            'user' => $ticket->user->name ?? 'Unknown',
            'due_date' => $ticket->due_date,
        ];
    }

    protected function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}