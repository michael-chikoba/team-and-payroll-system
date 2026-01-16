<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class ChatController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:department,custom,direct',
            'department_id' => 'nullable|exists:departments,id',
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'exists:users,id',
        ]);

        try {
            $user = $request->user();
            $employee = $user->employee()->with('business')->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found'
                ], 404);
            }

            if (!$employee->business_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee is not assigned to any business'
                ], 400);
            }

            DB::beginTransaction();

            $group = ChatGroup::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'],
                'business_id' => $employee->business_id,
                'department_id' => $validated['department_id'] ?? null,
                'created_by' => $user->id,
            ]);

            // Log event: Chat group created
            Log::info('Chat group created', [
                'event' => 'chat_group.created',
                'group_id' => $group->id,
                'group_name' => $group->name,
                'group_type' => $group->type,
                'business_id' => $employee->business_id,
                'created_by' => $user->id,
                'creator_name' => $user->name,
                'timestamp' => now()
            ]);

            $group->addMember($user->id, 'admin');

            $addedMembers = [];
            foreach ($validated['member_ids'] as $memberId) {
                if ($memberId != $user->id) {
                    $memberEmployee = Employee::where('user_id', $memberId)
                        ->where('business_id', $employee->business_id)
                        ->first();
                    
                    if (!$memberEmployee) {
                        Log::warning('User not in same business', [
                            'user_id' => $memberId,
                            'business_id' => $employee->business_id
                        ]);
                        continue;
                    }
                    
                    $group->addMember($memberId, 'member');
                    $addedMembers[] = $memberId;
                }
            }

            // Log event: Members added to group
            if (!empty($addedMembers)) {
                Log::info('Members added to chat group', [
                    'event' => 'chat_group.members_added',
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'added_by' => $user->id,
                    'member_ids' => $addedMembers,
                    'member_count' => count($addedMembers),
                    'timestamp' => now()
                ]);
            }

            ChatMessage::create([
                'chat_group_id' => $group->id,
                'user_id' => $user->id,
                'message' => "{$user->name} created the group",
                'type' => 'system',
            ]);

            DB::commit();

            $group->load([
                'members.employee',
                'creator.employee',
                'department'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Group created successfully',
                'group' => $this->formatGroup($group, $user)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create chat group', [
                'event' => 'chat_group.creation_failed',
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create group',
                'error' => $e->getMessage()
            ], 500);
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

            $existingGroup = ChatGroup::where('type', 'direct')
                ->where('business_id', $currentUser->employee->business_id)
                ->whereHas('members', function($q) use ($currentUser) {
                    $q->where('user_id', $currentUser->id);
                })
                ->whereHas('members', function($q) use ($otherUserId) {
                    $q->where('user_id', $otherUserId);
                })
                ->with(['members.employee', 'creator.employee'])
                ->first();

            if ($existingGroup) {
                // Log event: Existing direct message accessed
                Log::info('Existing direct message accessed', [
                    'event' => 'chat_group.direct_message_accessed',
                    'group_id' => $existingGroup->id,
                    'user_id' => $currentUser->id,
                    'other_user_id' => $otherUserId,
                    'timestamp' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'group' => $this->formatGroup($existingGroup, $currentUser),
                    'is_new' => false
                ]);
            }

            DB::beginTransaction();

            $group = ChatGroup::create([
                'name' => "Direct message: {$currentUser->name} & {$otherUser->name}",
                'type' => 'direct',
                'business_id' => $currentUser->employee->business_id,
                'created_by' => $currentUser->id,
            ]);

            $group->addMember($currentUser->id, 'admin');
            $group->addMember($otherUserId, 'admin');

            // Log event: New direct message created
            Log::info('New direct message created', [
                'event' => 'chat_group.direct_message_created',
                'group_id' => $group->id,
                'created_by' => $currentUser->id,
                'creator_name' => $currentUser->name,
                'other_user_id' => $otherUserId,
                'other_user_name' => $otherUser->name,
                'business_id' => $currentUser->employee->business_id,
                'timestamp' => now()
            ]);

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
                'event' => 'chat_group.direct_message_failed',
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
            'description' => $group->description,
            'type' => $group->type,
            'avatar' => $group->avatar,
            'member_count' => $group->members->count(),
            'members' => $group->members->map(function($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'avatar' => $member->avatar ?? null,
                    'role' => $member->pivot->role,
                    'employee_id' => $member->employee->id ?? null,
                    'department' => $member->employee->department ?? null,
                    'position' => $member->employee->position ?? null,
                    'is_online' => $member->is_online ?? false,
                    'last_seen' => $member->last_seen_at ?? null,
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

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $user->load('employee');
            
            if (!$user->employee) {
                Log::warning('User has no employee record', ['user_id' => $user->id]);
                return response()->json([
                    'success' => true,
                    'groups' => [],
                    'message' => 'No employee record found'
                ]);
            }

            if (!$user->employee->business_id) {
                Log::warning('Employee has no business_id', [
                    'user_id' => $user->id,
                    'employee_id' => $user->employee->id
                ]);
                return response()->json([
                    'success' => true,
                    'groups' => [],
                    'message' => 'Employee not assigned to any business'
                ]);
            }

            $groups = ChatGroup::forUser($user->id)
                ->with([
                    'members.employee',  // REMOVED ->department
                    'lastMessage',
                    'creator.employee'
                ])
                ->active()
                ->orderByDesc('updated_at')
                ->get()
                ->map(function($group) use ($user) {
                    try {
                        $membership = $group->memberships()->where('user_id', $user->id)->first();
                        
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
                            'description' => $group->description,
                            'type' => $group->type,
                            'avatar' => $group->avatar,
                            'member_count' => $group->members->count(),
                            'members_preview' => $group->members->take(3)->map(function($member) {
                                return [
                                    'id' => $member->id,
                                    'name' => $member->name,
                                    'avatar' => $member->avatar ?? null,
                                    'employee_id' => $member->employee->id ?? null,
                                    'department' => $member->employee->department ?? null,  // Use column directly
                                    'position' => $member->employee->position ?? null
                                ];
                            }),
                            'last_message' => $group->last_message,
                            'unread_count' => $unreadCount,
                            'is_muted' => $membership->is_muted ?? false,
                            'user_role' => $membership->role ?? 'member',
                            'created_at' => $group->created_at,
                            'updated_at' => $group->updated_at,
                        ];
                    } catch (\Exception $e) {
                        Log::error('Error mapping group', [
                            'group_id' => $group->id,
                            'error' => $e->getMessage()
                        ]);
                        return null;
                    }
                })
                ->filter();

            return response()->json([
                'success' => true,
                'groups' => $groups->values()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch chat groups', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'user_id' => $request->user()->id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch chat groups',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

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
            ->with('employee');  // REMOVED ->department

            if ($groupId) {
                $query->whereDoesntHave('chatGroups', function($q) use ($groupId) {
                    $q->where('chat_groups.id', $groupId);
                });
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhereHas('employee', function($q2) use ($search) {
                          $q2->where('position', 'like', "%{$search}%")
                             ->orWhere('department', 'like', "%{$search}%");  // Search department column
                      });
                });
            }

            $users = $query->orderBy('first_name')
                ->orderBy('last_name')
                ->limit(50)
                ->get()
                ->map(function($u) {
                    try {
                        return [
                            'id' => $u->id,
                            'name' => $u->name,
                            'email' => $u->email,
                            'avatar' => $u->avatar ?? null,
                            'employee_id' => $u->employee->id ?? null,
                            'department' => $u->employee->department ?? 'N/A',  // Use column directly
                            'position' => $u->employee->position ?? 'N/A',
                            'business_id' => $u->employee->business_id ?? null,
                        ];
                    } catch (\Exception $e) {
                        Log::error('Error mapping user', [
                            'user_id' => $u->id,
                            'error' => $e->getMessage()
                        ]);
                        return null;
                    }
                })
                ->filter()
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
                'members.employee',  // REMOVED ->department
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