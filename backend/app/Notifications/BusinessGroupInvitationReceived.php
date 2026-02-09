<?php

namespace App\Notifications;

use App\Models\BusinessGroupInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class BusinessGroupInvitationReceived extends Notification
{
    use Queueable;

    protected $invitation;

    public function __construct(BusinessGroupInvitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $groupName = $this->invitation->businessGroup->name;
        $invitedByBusiness = $this->invitation->invitedBy->currentBusiness->name ?? 'A business';
        
        return (new MailMessage)
            ->subject("Business Group Invitation: {$groupName}")
            ->greeting("Hello {$notifiable->first_name}!")
            ->line("{$invitedByBusiness} has invited your business to join the business group '{$groupName}'.")
            ->line("**Group Type:** " . ucfirst($this->invitation->businessGroup->group_type))
            ->line("**Proposed Role:** " . ucfirst($this->invitation->proposed_role))
            ->action('View Invitation', url("/invitations/{$this->invitation->id}"))
            ->line('This invitation will expire on ' . $this->invitation->expires_at->format('F j, Y'))
            ->line('Thank you for using our platform!');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'business_group_id' => $this->invitation->business_group_id,
            'group_name' => $this->invitation->businessGroup->name,
            'invited_by' => $this->invitation->invitedBy->full_name,
            'invited_by_business' => $this->invitation->invitedBy->currentBusiness->name ?? null,
            'proposed_role' => $this->invitation->proposed_role,
            'expires_at' => $this->invitation->expires_at,
            'type' => 'business_group_invitation',
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType($notifiable): string
    {
        return 'business_group_invitation';
    }
}