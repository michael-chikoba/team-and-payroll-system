<?php

namespace App\Services;

use App\Models\SlackIntegration;
use App\Models\SlackNotificationLog;
use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackService
{
    public function sendTicketNotification(Ticket $ticket, string $notificationType, array $additionalData = [])
    {
        try {
            $ticket->load(['user', 'approver', 'assignedUsers']);
            
            // Get business from ticket creator's employee
            $employee = $ticket->user->employee;
            if (!$employee) {
                Log::warning('No employee found for ticket creator', ['ticket_id' => $ticket->id]);
                return false;
            }

            // Get Slack integration for this business
            $slackIntegration = SlackIntegration::where('business_id', $employee->business_id)
                ->where('is_active', true)
                ->first();

            if (!$slackIntegration) {
                Log::info('No active Slack integration found', ['business_id' => $employee->business_id]);
                return false;
            }

            // Check if this notification type should be sent
            if (!$slackIntegration->shouldNotify($notificationType)) {
                Log::info('Notification type disabled', [
                    'type' => $notificationType,
                    'integration_id' => $slackIntegration->id
                ]);
                return false;
            }

            // Build message based on notification type
            $message = $this->buildMessage($ticket, $notificationType, $additionalData);

            // Send to Slack
            $response = $this->sendToSlack($slackIntegration, $message);

            // Log the notification
            $this->logNotification($ticket, $slackIntegration, $notificationType, $response);

            return $response['success'];

        } catch (\Exception $e) {
            Log::error('Failed to send Slack notification', [
                'ticket_id' => $ticket->id,
                'type' => $notificationType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    protected function buildMessage(Ticket $ticket, string $type, array $additionalData = []): array
    {
        $appUrl = config('app.url');
        $ticketUrl = "{$appUrl}/tickets/{$ticket->id}";
        
        $message = [
            'blocks' => []
        ];

        // Header
        $header = $this->getHeaderForType($type, $ticket);
        $message['blocks'][] = [
            'type' => 'header',
            'text' => [
                'type' => 'plain_text',
                'text' => $header,
                'emoji' => true
            ]
        ];

        // Ticket details
        $message['blocks'][] = [
            'type' => 'section',
            'fields' => [
                [
                    'type' => 'mrkdwn',
                    'text' => "*Ticket ID:*\n#{$ticket->id}"
                ],
                [
                    'type' => 'mrkdwn',
                    'text' => "*Type:*\n" . $this->getTypeEmoji($ticket->type) . " " . ucfirst(str_replace('_', ' ', $ticket->type))
                ],
                [
                    'type' => 'mrkdwn',
                    'text' => "*Priority:*\n" . $this->getPriorityEmoji($ticket->priority) . " " . ucfirst($ticket->priority)
                ],
                [
                    'type' => 'mrkdwn',
                    'text' => "*Status:*\n" . $this->getStatusEmoji($ticket->status) . " " . ucfirst(str_replace('_', ' ', $ticket->status))
                ]
            ]
        ];

        // Title and description
        $message['blocks'][] = [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => "*{$ticket->title}*\n" . substr($ticket->description, 0, 150) . (strlen($ticket->description) > 150 ? '...' : '')
            ]
        ];

        // Category info
        if ($ticket->category) {
            $categoryText = "*Category:* {$ticket->category}";
            if ($ticket->subcategory) {
                $categoryText .= " > {$ticket->subcategory}";
            }
            $message['blocks'][] = [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => $categoryText
                    ]
                ]
            ];
        }

        // Requester and assignee info
        $contextElements = [];
        
        $userName = $this->getUserName($ticket->user);
        $contextElements[] = [
            'type' => 'mrkdwn',
            'text' => "👤 *Requested by:* {$userName}"
        ];

        if ($ticket->approver) {
            $approverName = $this->getUserName($ticket->approver);
            $contextElements[] = [
                'type' => 'mrkdwn',
                'text' => "✅ *Approver:* {$approverName}"
            ];
        }

        if ($ticket->assignedUsers && $ticket->assignedUsers->count() > 0) {
            $assignedNames = $ticket->assignedUsers->map(fn($u) => $this->getUserName($u))->join(', ');
            $contextElements[] = [
                'type' => 'mrkdwn',
                'text' => "👥 *Assigned to:* {$assignedNames}"
            ];
        }

        if (!empty($contextElements)) {
            $message['blocks'][] = [
                'type' => 'context',
                'elements' => $contextElements
            ];
        }

        // Additional info based on notification type
        if ($type === 'status_changed' && isset($additionalData['old_status'])) {
            $message['blocks'][] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "Status changed from *{$additionalData['old_status']}* to *{$ticket->status}*"
                ]
            ];
        }

        if ($type === 'comment_added' && isset($additionalData['comment'])) {
            $commentText = substr($additionalData['comment'], 0, 200);
            $message['blocks'][] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "💬 *New Comment:*\n>{$commentText}"
                ]
            ];
        }

        // Divider
        $message['blocks'][] = [
            'type' => 'divider'
        ];

        // Actions
        $message['blocks'][] = [
            'type' => 'actions',
            'elements' => [
                [
                    'type' => 'button',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => '🔗 View Ticket',
                        'emoji' => true
                    ],
                    'url' => $ticketUrl,
                    'style' => 'primary'
                ]
            ]
        ];

        return $message;
    }

    protected function sendToSlack(SlackIntegration $integration, array $message): array
    {
        try {
            $response = Http::post($integration->webhook_url, $message);

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Slack API error', [
                'error' => $e->getMessage(),
                'integration_id' => $integration->id
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function logNotification(Ticket $ticket, SlackIntegration $integration, string $type, array $response)
    {
        SlackNotificationLog::create([
            'ticket_id' => $ticket->id,
            'slack_integration_id' => $integration->id,
            'notification_type' => $type,
            'status' => $response['success'] ? 'sent' : 'failed',
            'message' => json_encode($response['response'] ?? []),
            'error_message' => $response['error'] ?? null,
            'response_data' => $response
        ]);
    }

    protected function getHeaderForType(string $type, Ticket $ticket): string
    {
        return match($type) {
            'created' => '🎫 New Ticket Created',
            'approved' => '✅ Ticket Approved',
            'rejected' => '❌ Ticket Rejected',
            'status_changed' => '🔄 Ticket Status Updated',
            'assigned' => '👥 Ticket Assigned',
            'comment_added' => '💬 New Comment Added',
            'attachment_uploaded' => '📎 File Attached',
            default => '📋 Ticket Update'
        };
    }

    protected function getTypeEmoji(string $type): string
    {
        return match($type) {
            'issue' => '🐛',
            'request' => '📋',
            'change_request' => '🔧',
            default => '🎫'
        };
    }

    protected function getPriorityEmoji(string $priority): string
    {
        return match($priority) {
            'critical' => '🔴',
            'high' => '🟠',
            'medium' => '🟡',
            'low' => '🟢',
            default => '⚪'
        };
    }

    protected function getStatusEmoji(string $status): string
    {
        return match($status) {
            'pending' => '⏳',
            'approved' => '✅',
            'rejected' => '❌',
            'in_progress' => '🔄',
            'resolved' => '✔️',
            'closed' => '🔒',
            default => '📋'
        };
    }

    protected function getUserName($user): string
    {
        if (!$user) return 'Unknown User';
        if ($user->name) return $user->name;
        if ($user->first_name || $user->last_name) {
            return trim("{$user->first_name} {$user->last_name}");
        }
        return $user->email ?? 'Unknown User';
    }

    public function testConnection(SlackIntegration $integration): array
    {
        try {
            $message = [
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => '✅ *Slack Integration Test Successful!*\n\nYour helpdesk system is now connected to this channel.'
                        ]
                    ]
                ]
            ];

            $response = $this->sendToSlack($integration, $message);
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Test message sent successfully!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to send test message',
                    'error' => $response['error'] ?? 'Unknown error'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed',
                'error' => $e->getMessage()
            ];
        }
    }
}