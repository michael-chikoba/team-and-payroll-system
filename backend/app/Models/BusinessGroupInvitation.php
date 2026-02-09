<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\UserNotification; // ✅ Changed from Notification
use Carbon\Carbon;

class BusinessGroupInvitation extends Model
{
    protected $fillable = [
        'business_group_id',
        'invited_business_id',
        'invited_by_user_id',
        'proposed_role',
        'message',
        'status',
        'expires_at',
        'responded_at',
        'responded_by_user_id',
        'response_message',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    public function invitedBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'invited_business_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by_user_id');
    }

    // ==================== HELPER METHODS ====================

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Send notification to all admins of the invited business
     */
    public function sendNotification(): void
    {
        try {
            // Get the invited business
            $invitedBusiness = $this->invitedBusiness;
            
            if (!$invitedBusiness) {
                \Log::warning('Cannot send invitation notification: invited business not found', [
                    'invitation_id' => $this->id,
                    'invited_business_id' => $this->invited_business_id
                ]);
                return;
            }

            // Get all admin users for this business
            $adminUsers = \App\Models\User::whereHas('employee', function ($query) use ($invitedBusiness) {
                $query->where('business_id', $invitedBusiness->id);
            })
            ->where(function ($q) {
                $q->where('role', 'admin')
                  ->orWhereHas('roles', function ($roleQuery) {
                      $roleQuery->where('name', 'admin');
                  });
            })
            ->get();

            \Log::info('Sending business group invitation notifications', [
                'invitation_id' => $this->id,
                'invited_business_id' => $this->invited_business_id,
                'invited_business_name' => $invitedBusiness->name,
                'admin_count' => $adminUsers->count(),
                'admin_ids' => $adminUsers->pluck('id')->toArray(),
                'admin_emails' => $adminUsers->pluck('email')->toArray()
            ]);

            if ($adminUsers->isEmpty()) {
                \Log::warning('No admin users found for invited business', [
                    'invitation_id' => $this->id,
                    'business_id' => $invitedBusiness->id,
                    'business_name' => $invitedBusiness->name
                ]);
                return;
            }

            // Send notification to each admin
            foreach ($adminUsers as $admin) {
                try {
                    $notification = UserNotification::create([
                        'user_id' => $admin->id,
                        'type' => 'business_group_invitation',
                        'title' => 'Business Group Invitation',
                        'message' => "You've been invited to join \"{$this->businessGroup->name}\" business group as {$this->proposed_role}",
                        'data' => json_encode([
                            'invitation_id' => $this->id,
                            'business_group_id' => $this->business_group_id,
                            'group_name' => $this->businessGroup->name,
                            'invited_by' => $this->invitedBy->first_name . ' ' . $this->invitedBy->last_name,
                            'proposed_role' => $this->proposed_role,
                            'message' => $this->message,
                            'expires_at' => $this->expires_at ? $this->expires_at->toISOString() : null,
                        ]),
                        'action' => '/invitations',
                        'is_read' => false,
                    ]);

                    \Log::info('Notification created successfully', [
                        'notification_id' => $notification->id,
                        'user_id' => $admin->id,
                        'user_email' => $admin->email,
                        'type' => 'business_group_invitation'
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to create notification for admin', [
                        'admin_id' => $admin->id,
                        'admin_email' => $admin->email,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            \Log::info('Business group invitation notifications sent successfully', [
                'invitation_id' => $this->id,
                'notifications_created' => $adminUsers->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send business group invitation notifications', [
                'invitation_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function accept(User $user, ?string $message = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => 'accepted',
            'responded_at' => now(),
            'responded_by_user_id' => $user->id,
            'response_message' => $message,
        ]);

        // Create membership
        BusinessGroupMembership::create([
            'business_group_id' => $this->business_group_id,
            'business_id' => $this->invited_business_id,
            'role' => $this->proposed_role,
            'invited_by_user_id' => $this->invited_by_user_id,
            'approved_at' => now(),
            'approved_by_user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        // Log activity
        $this->businessGroup->logActivity(
            'invitation_accepted',
            "Business '{$this->invitedBusiness->name}' accepted invitation",
            $this->invited_business_id,
            $user->id
        );

        return true;
    }

    public function reject(User $user, ?string $message = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'responded_at' => now(),
            'responded_by_user_id' => $user->id,
            'response_message' => $message,
        ]);

        // Log activity
        $this->businessGroup->logActivity(
            'invitation_rejected',
            "Business '{$this->invitedBusiness->name}' rejected invitation",
            $this->invited_business_id,
            $user->id
        );

        return true;
    }

    public function cancel(User $user): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'responded_at' => now(),
            'responded_by_user_id' => $user->id,
        ]);

        return true;
    }

    // ==================== SCOPES ====================

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }
}