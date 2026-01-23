<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Models\User;
use App\Models\Employee;
use App\Models\ChatInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * Get all groups and channels for user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user || !$user->employee || !$user->employee->business_id) {
                return response()->json([
                    'success' => true,
                    'groups' => [],
                    'channels' => [],
                    'direct_messages' => [],
                ]);
            }

            $groups = ChatGroup::forUser($user->id)
                ->with(['members.employee', 'lastMessage.user', 'creator'])
                ->active()
                ->orderByDesc('updated_at')
                ->get();

            // Categorize groups
            $channels = $groups->filter(fn($g) => $g->is_channel)->values();
            $directMessages = $groups->filter(fn($g) => $g->type === 'direct')->values();
            $customGroups = $groups->filter(fn($g) => !$g->is_channel && $g->type !== 'direct')->values();

            $formatGroup = function($group) use ($user) {
                $membership = $group->memberships()->where('user_id', $user->id)->first();
                $unreadCount = $group->getUnreadCountForUser($user->id);

                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'display_name' => $group->getDisplayName(),
                    'description' => $group->description,
                    'type' => $group->type,
                    'is_channel' => $group->is_channel,
                    'is_private' => $group->is_private,
                    'is_favorite' => $membership->is_favorite ?? false,
                    'avatar' => $group->avatar,
                    'member_count' => $group->members->count(),
                    'last_message' => $group->lastMessage ? [
                        'id' => $group->lastMessage->id,
                        'message' => $group->lastMessage->message,
                        'user_name' => $group->lastMessage->user->name ?? 'Unknown',
                        'created_at' => $group->lastMessage->created_at,
                    ] : null,
                    'unread_count' => $unreadCount,
                    'is_muted' => $membership->is_muted ?? false,
                    'user_role' => $membership->role ?? 'member',
                    'updated_at' => $group->updated_at,
                ];
            };

            return response()->json([
                'success' => true,
                'channels' => $channels->map($formatGroup),
                'direct_messages' => $directMessages->map($formatGroup),
                'groups' => $customGroups->map($formatGroup),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch chat groups', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? 'unknown',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch chat groups',
            ], 500);
        }
    }

    /**
     * Create a new channel or group
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:channel,group,department,direct',
            'is_private' => 'boolean',
            'department_id' => 'nullable|exists:departments,id|required_if:type,department',
            'member_ids' => 'sometimes|array',
            'member_ids.*' => 'exists:users,id',
        ]);

        try {
            $user = $request->user();
            $employee = $user->employee()->with('business')->first();

            if (!$employee || !$employee->business_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee is not assigned to any business'
                ], 400);
            }

            DB::beginTransaction();

            $groupData = [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'],
                'is_private' => $validated['is_private'] ?? false,
                'business_id' => $employee->business_id,
                'department_id' => $validated['department_id'] ?? null,
                'created_by' => $user->id,
            ];

            // Set is_channel based on type if not provided
            $groupData['is_channel'] = in_array($validated['type'], ['channel', 'department']);

            $group = ChatGroup::create($groupData);

            // Add creator as admin
            $group->addMember($user->id, 'admin');

            // Add other members if provided
            if (isset($validated['member_ids'])) {
                foreach ($validated['member_ids'] as $memberId) {
                    if ($memberId != $user->id) {
                        $memberEmployee = Employee::where('user_id', $memberId)
                            ->where('business_id', $employee->business_id)
                            ->first();

                        if ($memberEmployee) {
                            $group->addMember($memberId, 'member', $user->id);
                        }
                    }
                }
            }

            DB::commit();

            $group->load(['members.employee', 'creator']);

            return response()->json([
                'success' => true,
                'message' => ($group->is_channel ? 'Channel' : 'Group') . ' created successfully',
                'group' => $this->formatGroupDetails($group, $user)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create chat group', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create ' . (isset($validated['type']) && in_array($validated['type'], ['channel', 'department']) ? 'channel' : 'group'),
            ], 500);
        }
    }

    /**
     * Get browsable public channels
     */
    public function getBrowsableChannels(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $employee = $user->employee;

            if (!$employee || !$employee->business_id) {
                return response()->json(['success' => true, 'channels' => []]);
            }

            $search = $request->query('search');

            $query = ChatGroup::publicChannels()
                ->forBusiness($employee->business_id)
                ->active()
                ->whereDoesntHave('members', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->with(['creator', 'members']);

            if ($search) {
                $query->searchByName($search);
            }

            $channels = $query->orderBy('name')->get()->map(function($channel) {
                return [
                    'id' => $channel->id,
                    'name' => $channel->name,
                    'display_name' => $channel->getDisplayName(),
                    'description' => $channel->description,
                    'member_count' => $channel->members->count(),
                    'created_by' => $channel->creator->name,
                    'created_at' => $channel->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'channels' => $channels
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch browsable channels', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch channels'], 500);
        }
    }

    /**
     * Join a public channel
     */
    public function joinChannel(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $channel = ChatGroup::findOrFail($id);

            if (!$channel->is_channel) {
                return response()->json([
                    'success' => false,
                    'message' => 'This is not a channel'
                ], 400);
            }

            if ($channel->is_private) {
                return response()->json([
                    'success' => false,
                    'message' => 'This is a private channel. You need an invitation to join.'
                ], 403);
            }

            if ($channel->isMember($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already a member of this channel'
                ], 400);
            }

            $channel->addMember($user->id, 'member');

            return response()->json([
                'success' => true,
                'message' => 'Successfully joined the channel'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to join channel', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to join channel'], 500);
        }
    }

    /**
     * Leave a channel/group
     */
    public function leaveGroup(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($id);

            if (!$group->isMember($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not a member of this ' . ($group->is_channel ? 'channel' : 'group')
                ], 400);
            }

            // Check if user is the only admin
            if ($group->isAdmin($user->id)) {
                $adminCount = $group->memberships()->where('role', 'admin')->count();
                if ($adminCount <= 1 && $group->members->count() > 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are the only admin. Please assign another admin before leaving.'
                    ], 400);
                }
            }

            $group->removeMember($user->id, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Successfully left the ' . ($group->is_channel ? 'channel' : 'group')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to leave group', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to leave group'], 500);
        }
    }

    /**
     * Archive/Unarchive channel or group
     */
    public function toggleArchive(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($id);

            if (!$group->isAdmin($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can archive/unarchive'
                ], 403);
            }

            if ($group->is_archived) {
                $group->unarchive($user->id);
                $message = 'Unarchived successfully';
            } else {
                $group->archive($user->id);
                $message = 'Archived successfully';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'is_archived' => $group->is_archived
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle archive', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to archive/unarchive'], 500);
        }
    }

    /**
     * Toggle favorite
     */
    public function toggleFavorite(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($id);

            if (!$group->isMember($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be a member to favorite this'
                ], 403);
            }

            $isFavorite = $group->toggleFavorite($user->id);

            return response()->json([
                'success' => true,
                'is_favorite' => $isFavorite,
                'message' => $isFavorite ? 'Added to favorites' : 'Removed from favorites'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle favorite', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to toggle favorite'], 500);
        }
    }

    /**
     * Update typing status
     */
    public function updateTypingStatus(Request $request, int $groupId): JsonResponse
    {
        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($groupId);

            if (!$group->isMember($user->id)) {
                return response()->json(['success' => false], 403);
            }

            // Assuming you have a ChatTypingIndicator model
            // ChatTypingIndicator::updateOrCreate(...)

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Get typing users
     */
    public function getTypingUsers(int $groupId): JsonResponse
    {
        try {
            // Logic to get typing users
            $typingUsers = []; // Placeholder

            return response()->json([
                'success' => true,
                'typing_users' => $typingUsers,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Invite user
     */
    public function inviteUser(Request $request, int $groupId): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($groupId);

            if (!$group->isAdmin($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can invite users'
                ], 403);
            }

            ChatInvitation::create([
                'chat_group_id' => $groupId,
                'invited_user_id' => $validated['user_id'],
                'invited_by' => $user->id,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invitation sent successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Accept invitation
     */
    public function acceptInvitation(Request $request, int $invitationId): JsonResponse
    {
        try {
            $user = $request->user();
            $invitation = ChatInvitation::where('id', $invitationId)
                ->where('invited_user_id', $user->id)
                ->where('status', 'pending')
                ->firstOrFail();

            $invitation->accept();

            return response()->json([
                'success' => true,
                'message' => 'Invitation accepted',
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'avatar' => 'nullable|string',
        ]);

        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($id);

            if (!$group->isAdmin($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only group admins can update the group'
                ], 403);
            }

            $oldValues = [
                'name' => $group->name,
                'description' => $group->description,
                'avatar' => $group->avatar
            ];

            $group->update($validated);

            // Log event: Group updated
            Log::info('Chat group updated', [
                'event' => 'chat_group.updated',
                'group_id' => $group->id,
                'updated_by' => $user->id,
                'updater_name' => $user->name,
                'old_values' => $oldValues,
                'new_values' => $validated,
                'timestamp' => now()
            ]);

            if (isset($validated['name'])) {
                ChatMessage::create([
                    'chat_group_id' => $group->id,
                    'user_id' => $user->id,
                    'message' => "{$user->name} changed group name to '{$validated['name']}'",
                    'type' => 'system',
                ]);
            }

            $group->load(['members.employee', 'creator.employee']);

            return response()->json([
                'success' => true,
                'message' => 'Group updated successfully',
                'group' => $this->formatGroup($group, $user)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update group', [
                'event' => 'chat_group.update_failed',
                'error' => $e->getMessage(),
                'group_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addMembers(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        try {
            $user = $request->user();
            $group = ChatGroup::with(['members.employee'])->findOrFail($id);

            if (!$group->isAdmin($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only group admins can add members'
                ], 403);
            }

            $added = [];
            $failed = [];

            foreach ($validated['user_ids'] as $userId) {
                if (!$group->isMember($userId)) {
                    $memberEmployee = Employee::where('user_id', $userId)
                        ->where('business_id', $group->business_id)
                        ->first();

                    if (!$memberEmployee) {
                        $failed[] = $userId;
                        continue;
                    }

                    $group->addMember($userId, 'member');

                    $added[] = $userId;

                    $addedUser = User::find($userId);
                    ChatMessage::create([
                        'chat_group_id' => $group->id,
                        'user_id' => $user->id,
                        'message' => "{$user->name} added {$addedUser->name}",
                        'type' => 'system',
                    ]);
                }
            }

            // Log event: Members added
            if (!empty($added)) {
                Log::info('Members added to existing chat group', [
                    'event' => 'chat_group.members_added',
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'added_by' => $user->id,
                    'adder_name' => $user->name,
                    'member_ids' => $added,
                    'success_count' => count($added),
                    'failed_count' => count($failed),
                    'timestamp' => now()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => count($added) . ' member(s) added successfully' .
                             (count($failed) > 0 ? ', ' . count($failed) . ' failed (not in same business)' : ''),
                'added_count' => count($added),
                'failed_count' => count($failed)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to add members', [
                'event' => 'chat_group.add_members_failed',
                'error' => $e->getMessage(),
                'group_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add members',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function removeMember(Request $request, int $id, int $userId): JsonResponse
    {
        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($id);

            if ($group->isAdmin($userId)) {
                $adminCount = $group->memberships()->where('role', 'admin')->count();
                if ($adminCount <= 1 && $userId !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot remove the only admin. Promote another admin first.'
                    ], 400);
                }
            }

            if ($userId !== $user->id && !$group->isAdmin($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only group admins can remove other members'
                ], 403);
            }

            $removedUser = User::find($userId);
            $removed = $group->removeMember($userId);

            if ($removed) {
                // Log event: Member removed
                Log::info('Member removed from chat group', [
                    'event' => 'chat_group.member_removed',
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'removed_user_id' => $userId,
                    'removed_user_name' => $removedUser->name,
                    'removed_by' => $user->id,
                    'remover_name' => $user->name,
                    'is_self_removal' => $userId === $user->id,
                    'timestamp' => now()
                ]);

                ChatMessage::create([
                    'chat_group_id' => $group->id,
                    'user_id' => $user->id,
                    'message' => $userId === $user->id
                        ? "{$user->name} left the group"
                        : "{$user->name} removed {$removedUser->name}",
                    'type' => 'system',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Member removed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to remove member', [
                'event' => 'chat_group.remove_member_failed',
                'error' => $e->getMessage(),
                'group_id' => $id,
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateMemberRole(Request $request, int $groupId, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,member'
        ]);

        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($groupId);

            if (!$group->isAdmin($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only group admins can change roles'
                ], 403);
            }

            $membership = $group->memberships()->where('user_id', $userId)->firstOrFail();
            $oldRole = $membership->role;

            if ($membership->role === 'admin' && $validated['role'] === 'member') {
                $adminCount = $group->memberships()->where('role', 'admin')->count();
                if ($adminCount <= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot demote the only admin. Promote another admin first.'
                    ], 400);
                }
            }

            $membership->update(['role' => $validated['role']]);

            $targetUser = User::find($userId);

            // Log event: Member role updated
            Log::info('Chat group member role updated', [
                'event' => 'chat_group.member_role_updated',
                'group_id' => $group->id,
                'group_name' => $group->name,
                'target_user_id' => $userId,
                'target_user_name' => $targetUser->name,
                'old_role' => $oldRole,
                'new_role' => $validated['role'],
                'updated_by' => $user->id,
                'updater_name' => $user->name,
                'timestamp' => now()
            ]);

            ChatMessage::create([
                'chat_group_id' => $group->id,
                'user_id' => $user->id,
                'message' => "{$user->name} changed {$targetUser->name}'s role to {$validated['role']}",
                'type' => 'system',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Member role updated successfully',
                'role' => $validated['role']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update member role', [
                'event' => 'chat_group.update_role_failed',
                'error' => $e->getMessage(),
                'group_id' => $groupId,
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update member role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function toggleMute(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($id);

            if (!$group->isMember($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not a member of this group'
                ], 403);
            }

            $membership = $group->memberships()->where('user_id', $user->id)->firstOrFail();
            $oldMuteStatus = $membership->is_muted;

            $membership->update(['is_muted' => !$membership->is_muted]);

            // Log event: Mute toggled
            Log::info('Chat group mute toggled', [
                'event' => 'chat_group.mute_toggled',
                'group_id' => $group->id,
                'group_name' => $group->name,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'old_status' => $oldMuteStatus ? 'muted' : 'unmuted',
                'new_status' => $membership->is_muted ? 'muted' : 'unmuted',
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => true,
                'is_muted' => $membership->is_muted,
                'message' => $membership->is_muted
                    ? 'Group muted successfully'
                    : 'Group unmuted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle mute', [
                'event' => 'chat_group.toggle_mute_failed',
                'error' => $e->getMessage(),
                'group_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle mute',
                'error' => $e->getMessage()
            ], 500);
        }
    }

   public function getOrCreateDirectMessage(Request $request): JsonResponse
{
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id|different:' . $request->user()->id,
    ]);

    try {
        $currentUser = $request->user();
        $otherUserId = $validated['user_id'];

        $currentUser->load('employee.business');
        $otherUser = User::with('employee.business')->findOrFail($otherUserId);

        if (!$currentUser->employee || !$otherUser->employee) {
            return response()->json([
                'success' => false,
                'message' => 'Both users must have employee profiles'
            ], 400);
        }

        if ($currentUser->employee->business_id !== $otherUser->employee->business_id) {
            return response()->json([
                'success' => false,
                'message' => 'Can only message users in the same business'
            ], 403);
        }

        // Create names with fallbacks
        $currentUserName = $currentUser->name ?: ($currentUser->first_name . ' ' . $currentUser->last_name) ?: 'User ' . $currentUser->id;
        $otherUserName = $otherUser->name ?: ($otherUser->first_name . ' ' . $otherUser->last_name) ?: 'User ' . $otherUser->id;

        // Check for existing direct message (FIXED QUERY)
        $existingGroup = ChatGroup::where('type', 'direct')
            ->where('business_id', $currentUser->employee->business_id)
            ->where(function($query) use ($currentUser, $otherUser) {
                $query->whereHas('members', function($q) use ($currentUser) {
                    $q->where('user_id', $currentUser->id);
                })->whereHas('members', function($q) use ($otherUser) {
                    $q->where('user_id', $otherUser->id);
                });
            })
            ->with(['members.employee', 'creator.employee'])
            ->first();

        if ($existingGroup) {
            // Update the name if it's incorrect
            $correctName = "Direct message: {$currentUserName} ({$currentUser->id}) & {$otherUserName} ({$otherUser->id})";
            if ($existingGroup->name !== $correctName) {
                $existingGroup->update(['name' => $correctName]);
                $existingGroup->refresh();
            }

            return response()->json([
                'success' => true,
                'group' => $this->formatGroup($existingGroup, $currentUser),
                'is_new' => false
            ]);
        }

        DB::beginTransaction();

        // Create with IDs in the name for easier parsing
        $group = ChatGroup::create([
            'name' => "Direct message: {$currentUserName} ({$currentUser->id}) & {$otherUserName} ({$otherUser->id})",
            'display_name' => $otherUserName, // Set display_name to the other user's name
            'type' => 'direct',
            'business_id' => $currentUser->employee->business_id,
            'created_by' => $currentUser->id,
            'is_channel' => false,
        ]);

        $group->addMember($currentUser->id, 'admin');
        $group->addMember($otherUserId, 'admin');

        DB::commit();

        $group->load(['members.employee', 'creator.employee']);

        return response()->json([
            'success' => true,
            'group' => $this->formatGroup($group, $currentUser),
            'is_new' => true
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Failed to get/create direct message', [
            'error' => $e->getMessage(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to create direct message',
            'error' => $e->getMessage()
        ], 500);
    }
}

    private function formatGroupDetails(ChatGroup $group, User $user)
    {
        $membership = $group->memberships()->where('user_id', $user->id)->first();

        return [
            'id' => $group->id,
            'name' => $group->name,
            'display_name' => $group->getDisplayName(),
            'description' => $group->description,
            'type' => $group->type,
            'is_channel' => $group->is_channel,
            'is_private' => $group->is_private,
            'is_favorite' => $membership->is_favorite ?? false,
            'avatar' => $group->avatar,
            'member_count' => $group->members->count(),
            'members' => $group->members->map(function($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'avatar' => $member->avatar,
                    'role' => $member->pivot->role,
                    'employee_id' => $member->employee->id ?? null,
                    'department' => $member->employee->department ?? null,
                    'position' => $member->employee->position ?? null,
                ];
            }),
            'creator' => [
                'id' => $group->creator->id,
                'name' => $group->creator->name,
            ],
            'user_role' => $membership->role ?? 'member',
            'is_muted' => $membership->is_muted ?? false,
            'unread_count' => $group->getUnreadCountForUser($user->id),
            'created_at' => $group->created_at,
            'updated_at' => $group->updated_at,
        ];
    }

    // Helper method - keep your existing formatGroup method
    private function formatGroup(ChatGroup $group, User $user, $membership = null)
    {
        if (!$membership) {
            $membership = $group->memberships()->where('user_id', $user->id)->first();
        }

        $unreadCount = 0;
        if ($membership) {
            $unreadCount = $group->messages()
                ->where('created_at', '>', $membership->last_read_at ?? $group->created_at)
                ->where('user_id', '!=', $user->id)
                ->count();
        }

        return [
            'id' => $group->id,
            'name' => $group->name,
            'display_name' => $group->getDisplayName(),
            'description' => $group->description,
            'type' => $group->type,
            'avatar' => $group->avatar,
            'member_count' => $group->members->count(),
            'members' => $group->members->map(function($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'avatar' => $u->avatar ?? null,
                    'role' => $u->pivot->role,
                    'employee_id' => $u->employee->id ?? null,
                    'department' => $u->employee->department ?? null,
                    'position' => $u->employee->position ?? null,
                    'is_online' => $u->is_online ?? false,
                    'last_seen' => $u->last_seen_at ?? null,
                ];
            }),
            'creator' => [
                'id' => $group->creator->id,
                'name' => $group->creator->name,
                'employee_id' => $group->creator->employee->id ?? null,
            ],
            'department' => $group->department ? [
                'id' => $group->department->id,
                'name' => $group->department->name,
            ] : null,
            'user_role' => $membership->role ?? 'member',
            'is_muted' => $membership->is_muted ?? false,
            'unread_count' => $unreadCount,
            'created_at' => $group->created_at,
            'updated_at' => $group->updated_at,
            'business_id' => $group->business_id,
        ];
    }

    public function getMembers(Request $request, int $groupId): JsonResponse
{
    try {
        $user = $request->user();
        $group = ChatGroup::with('members.employee')->findOrFail($groupId);

        if (!$group->isMember($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this group'
            ], 403);
        }

        $members = $group->members->map(function ($member) {
            return [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'avatar' => $member->avatar,
                'role' => $member->pivot->role,
                'employee_id' => $member->employee->id ?? null,
                'department' => $member->employee->department ?? null,
                'position' => $member->employee->position ?? null,
            ];
        });

        return response()->json([
            'success' => true,
            'members' => $members,
            'total' => $members->count()
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to fetch group members', [
            'group_id' => $groupId,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch members'
        ], 500);
    }
}

  /**
     * Get available users for chat (fixed ordering issue)
     */
    public function getAvailableUsers(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $employee = $user->employee;

            if (!$employee) {
                Log::warning('User has no employee record', ['user_id' => $user->id]);
                return response()->json([
                    'success' => true,
                    'users' => [],
                    'total' => 0,
                    'message' => 'No employee record found'
                ]);
            }

            if (!$employee->business_id) {
                Log::warning('Employee has no business_id', ['employee_id' => $employee->id]);
                return response()->json([
                    'success' => true,
                    'users' => [],
                    'total' => 0,
                    'message' => 'Employee not assigned to any business'
                ]);
            }

            $groupId = $request->query('group_id');
            $search = $request->query('search');

            $query = User::whereHas('employee', function($q) use ($employee) {
                $q->where('business_id', $employee->business_id);
            })
            ->where('id', '!=', $user->id)
            ->with('employee');

            if ($groupId) {
                $query->whereDoesntHave('chatGroups', function($q) use ($groupId) {
                    $q->where('chat_groups.id', $groupId);
                });
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->whereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ["%{$search}%"])
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhereHas('employee', function($q2) use ($search) {
                          $q2->where('position', 'like', "%{$search}%")
                             ->orWhere('department', 'like', "%{$search}%");
                      });
                });
            }

            // Fix: Order by first_name instead of 'name'
            $users = $query->orderBy('first_name')
                ->orderBy('last_name')
                ->limit(50)
                ->get()
                ->filter(function($u) {
                    // Filter out users without names
                    return !empty($u->first_name) || !empty($u->last_name) || !empty($u->email);
                })
                ->map(function($u) {
                    return [
                        'id' => $u->id,
                        'name' => $u->name, // This now uses the accessor
                        'first_name' => $u->first_name,
                        'last_name' => $u->last_name,
                        'email' => $u->email,
                        'avatar' => $u->avatar ?? null,
                        'employee_id' => $u->employee->id ?? null,
                        'department' => $u->employee->department ?? 'N/A',
                        'position' => $u->employee->position ?? 'N/A',
                        'business_id' => $u->employee->business_id ?? null,
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'users' => $users,
                'total' => $users->count(),
                'business_id' => $employee->business_id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch available users', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'user_id' => $request->user()->id ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            $group = ChatGroup::with([
                'members.employee',
                'creator.employee',
                'department'
            ])->findOrFail($id);

            if (!$group->isMember($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not a member of this group'
                ], 403);
            }

            $membership = $group->memberships()->where('user_id', $user->id)->first();

            return response()->json([
                'success' => true,
                'group' => $this->formatGroup($group, $user, $membership)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch group details', [
                'error' => $e->getMessage(),
                'group_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch group details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($id);

            if (!$group->isMember($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not a member of this group'
                ], 403);
            }

            $membership = $group->memberships()
                ->where('user_id', $user->id)
                ->firstOrFail();

            $membership->update(['last_read_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Marked as read',
                'last_read_at' => $membership->last_read_at
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to mark as read', [
                'error' => $e->getMessage(),
                'group_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}