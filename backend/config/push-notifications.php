<?php

return [
    /*
    |--------------------------------------------------------------------------
    | VAPID Configuration
    |--------------------------------------------------------------------------
    |
    | VAPID (Voluntary Application Server Identification) keys for Web Push
    | Generate using: php artisan push:generate-vapid-keys
    |
    */
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@example.com'),
        'public_key' => env('VAPID_PUBLIC_KEY', ''),
        'private_key' => env('VAPID_PRIVATE_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Types
    |--------------------------------------------------------------------------
    |
    | Supported notification types and their default settings
    |
    */
    'types' => [
        'business_group_invitation' => [
            'title' => 'Business Group Invitation',
            'icon' => '/images/icons/group-invitation.png',
            'badge' => '/images/icons/badge.png',
            'requires_action' => true,
            'actions' => [
                ['action' => 'accept', 'title' => 'Accept'],
                ['action' => 'decline', 'title' => 'Decline'],
            ]
        ],
        'task_assigned' => [
            'title' => 'New Task Assigned',
            'icon' => '/images/icons/task.png',
            'badge' => '/images/icons/badge.png',
            'requires_action' => true,
            'actions' => [
                ['action' => 'view', 'title' => 'View Task'],
            ]
        ],
        'schedule_updated' => [
            'title' => 'Schedule Updated',
            'icon' => '/images/icons/calendar.png',
            'badge' => '/images/icons/badge.png',
            'requires_action' => false,
        ],
        'leave_request' => [
            'title' => 'Leave Request',
            'icon' => '/images/icons/leave.png',
            'badge' => '/images/icons/badge.png',
            'requires_action' => true,
            'actions' => [
                ['action' => 'approve', 'title' => 'Approve'],
                ['action' => 'reject', 'title' => 'Reject'],
            ]
        ],
        'ticket_created' => [
            'title' => 'New Ticket',
            'icon' => '/images/icons/ticket.png',
            'badge' => '/images/icons/badge.png',
            'requires_action' => true,
            'actions' => [
                ['action' => 'view', 'title' => 'View Ticket'],
            ]
        ],
        'reminder' => [
            'title' => 'Reminder',
            'icon' => '/images/icons/reminder.png',
            'badge' => '/images/icons/badge.png',
            'requires_action' => false,
        ],
        'system_announcement' => [
            'title' => 'System Announcement',
            'icon' => '/images/icons/announcement.png',
            'badge' => '/images/icons/badge.png',
            'requires_action' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Queue and connection settings for push notifications
    |
    */
    'queue' => [
        'enabled' => env('PUSH_QUEUE_ENABLED', true),
        'connection' => env('PUSH_QUEUE_CONNECTION', 'redis'),
        'queue' => env('PUSH_QUEUE_NAME', 'push-notifications'),
    ],

    /*
    |--------------------------------------------------------------------------
    | TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | Default TTL for push notifications in seconds (4 weeks)
    |
    */
    'ttl' => env('PUSH_TTL', 2419200),

    /*
    |--------------------------------------------------------------------------
    | Batch Settings
    |--------------------------------------------------------------------------
    |
    | Settings for batch sending notifications
    |
    */
    'batch' => [
        'size' => env('PUSH_BATCH_SIZE', 100),
        'delay' => env('PUSH_BATCH_DELAY', 1), // seconds between batches
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    |
    | Number of retries for failed notifications
    |
    */
    'retry' => [
        'attempts' => env('PUSH_RETRY_ATTEMPTS', 3),
        'delay' => env('PUSH_RETRY_DELAY', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable detailed logging for push notifications
    |
    */
    'logging' => [
        'enabled' => env('PUSH_LOGGING_ENABLED', true),
        'channel' => env('PUSH_LOG_CHANNEL', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Notification Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for notifications
    |
    */
    'defaults' => [
        'urgency' => 'normal', // very-low, low, normal, high
        'topic' => null,
    ],
];