<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlackNotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'slack_integration_id',
        'notification_type',
        'status',
        'message',
        'error_message',
        'response_data',
    ];

    protected $casts = [
        'response_data' => 'array',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function slackIntegration()
    {
        return $this->belongsTo(SlackIntegration::class);
    }
}