<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use App\Models\User;
use App\Events\TaskAssigned;
use App\Models\Employee;
use App\Models\Subtask;
use App\Models\TaskHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TaskController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
           
            Log::info('Fetching tasks', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'user_email' => $user->email
            ]);
           
            // Get user's employee record and business
            $userEmployee = $user->employee;
            
            if (!$userEmployee || !$userEmployee->business_id) {
                // If user has no business, only show tasks they created or are assigned to
                $tasks = Task::with([
                    'assignedTo.employee', 
                    'createdBy', 
                    'comments.user',
                    'subtasks.assignee',
                    'workLogs.user',
                    'history.user',
                    'linkedItems.linkedTask'
                ])
                ->where(function($query) use ($user) {
                    $query->where('created_by', $user->id)
                          ->orWhere('assigned_to', $user->id);
                })
                ->orderBy('deadline', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();
            } else {
                $business = $userEmployee->business;
                
                // Get business group IDs where cross-business task assignment is allowed
                $groupIds = $business->activeBusinessGroups()
                    ->wherePivot('can_assign_cross_business_tasks', true)
                    ->pluck('business_groups.id');
                
                Log::info('Business groups with cross-business task permission', [
                    'business_id' => $userEmployee->business_id,
                    'group_ids' => $groupIds->toArray()
                ]);
                
                if ($groupIds->isNotEmpty()) {
                    // Get other business IDs in the same groups
                    $otherBusinessIds = DB::table('business_group_memberships')
                        ->whereIn('business_group_id', $groupIds)
                        ->where('business_id', '!=', $userEmployee->business_id)
                        ->where('status', 'active')
                        ->pluck('business_id');
                    
                    // Get user IDs from current business AND grouped businesses
                    $businessEmployeeUserIds = Employee::where('business_id', $userEmployee->business_id)
                        ->pluck('user_id')
                        ->toArray();
                    
                    $groupBusinessUserIds = [];
                    if ($otherBusinessIds->isNotEmpty()) {
                        $groupBusinessUserIds = Employee::whereIn('business_id', $otherBusinessIds)
                            ->pluck('user_id')
                            ->toArray();
                    }
                    
                    $allAccessibleUserIds = array_merge($businessEmployeeUserIds, $groupBusinessUserIds);
                    
                    Log::info('Cross-business task access', [
                        'current_business_users' => count($businessEmployeeUserIds),
                        'group_business_users' => count($groupBusinessUserIds),
                        'total_accessible_users' => count($allAccessibleUserIds)
                    ]);
                    
                    // Show tasks from current business AND cross-business tasks
                    $tasks = Task::with([
                        'assignedTo.employee.business', 
                        'createdBy.employee.business', 
                        'comments.user',
                        'subtasks.assignee',
                        'workLogs.user',
                        'history.user',
                        'linkedItems.linkedTask'
                    ])
                    ->where(function($query) use ($allAccessibleUserIds) {
                        $query->whereIn('assigned_to', $allAccessibleUserIds)
                              ->orWhereIn('created_by', $allAccessibleUserIds);
                    })
                    ->orderBy('deadline', 'asc')
                    ->orderBy('created_at', 'desc')
                    ->get();
                } else {
                    // No groups - show only current business tasks
                    $businessEmployeeUserIds = Employee::where('business_id', $userEmployee->business_id)
                        ->pluck('user_id')
                        ->toArray();
                    
                    $tasks = Task::with([
                        'assignedTo.employee', 
                        'createdBy', 
                        'comments.user',
                        'subtasks.assignee',
                        'workLogs.user',
                        'history.user',
                        'linkedItems.linkedTask'
                    ])
                    ->where(function($query) use ($businessEmployeeUserIds) {
                        $query->whereIn('assigned_to', $businessEmployeeUserIds)
                              ->orWhereIn('created_by', $businessEmployeeUserIds);
                    })
                    ->orderBy('deadline', 'asc')
                    ->orderBy('created_at', 'desc')
                    ->get();
                }
            }
            
            Log::info('Tasks fetched', [
                'count' => $tasks->count(),
                'user_role' => $user->role
            ]);

            // Format tasks with additional employee info
            $formattedTasks = $tasks->map(function ($task) {
                $assignedUserEmployee = $task->assignedTo->employee ?? null;
                $createdByEmployee = $task->createdBy->employee ?? null;
             
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'deadline' => $task->deadline,
                    'created_at' => $task->created_at,
                    'updated_at' => $task->updated_at,
                    'assigned_to' => [
                        'id' => $task->assignedTo->id,
                        'first_name' => $task->assignedTo->first_name,
                        'last_name' => $task->assignedTo->last_name,
                        'email' => $task->assignedTo->email,
                        'name' => $task->assignedTo->first_name . ' ' . $task->assignedTo->last_name,
                        'employee_id' => $assignedUserEmployee->employee_id ?? null,
                        'position' => $assignedUserEmployee->position ?? null,
                        'department' => $assignedUserEmployee->department ?? null,
                        'business_id' => $assignedUserEmployee->business_id ?? null,
                        'business_name' => $assignedUserEmployee->business->name ?? null,
                    ],
                    'created_by' => [
                        'id' => $task->createdBy->id,
                        'first_name' => $task->createdBy->first_name,
                        'last_name' => $task->createdBy->last_name,
                        'email' => $task->createdBy->email,
                        'name' => $task->createdBy->first_name . ' ' . $task->createdBy->last_name,
                        'business_id' => $createdByEmployee->business_id ?? null,
                        'business_name' => $createdByEmployee->business->name ?? null,
                    ],
                    'comments' => $task->comments ?? [],
                    'subtasks' => $task->subtasks ?? [],
                    'history' => $task->history ?? [],
                    'worklogs' => $task->workLogs ?? [],
                    'linked_items' => $task->linkedItems ?? [],
                ];
            });

            return response()->json([
                'tasks' => $formattedTasks,
                'user_role' => $user->role
            ]);
           
        } catch (\Exception $e) {
            Log::error('Failed to fetch tasks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
           
            return response()->json([
                'message' => 'Failed to fetch tasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'priority' => 'required|in:low,moderate,high,critical',
                'assigned_to' => 'required|exists:users,id',
                'deadline' => 'nullable|date',
            ]);

            $currentUser = Auth::user();
            
            Log::info('Task creation attempt', [
                'user_id' => $currentUser->id,
                'user_role' => $currentUser->role,
                'assigned_to' => $validated['assigned_to']
            ]);

            // Verify the assigned user has an employee record
            $assignedUser = User::with('employee.business')->find($validated['assigned_to']);
           
            if (!$assignedUser) {
                return response()->json([
                    'message' => 'Assigned user not found'
                ], 404);
            }

            // Get current user's employee record
            $currentUserEmployee = $currentUser->employee;
            
            if (!$currentUserEmployee) {
                Log::warning('User has no employee record', [
                    'user_id' => $currentUser->id,
                    'user_role' => $currentUser->role
                ]);
                return response()->json([
                    'message' => 'You must have an employee record to create tasks'
                ], 400);
            }

            // All users must have an employee record to be assigned tasks
            if (!$assignedUser->employee) {
                return response()->json([
                    'message' => 'Assigned user does not have an employee record'
                ], 400);
            }

            // Check if assignment is allowed
            $canAssign = false;
            
            // Same business - always allowed
            if ($currentUserEmployee->business_id === $assignedUser->employee->business_id) {
                $canAssign = true;
                Log::info('Same business assignment');
            } else {
                // Check cross-business permission via business groups
                $business = $currentUserEmployee->business;
                $groupIds = $business->activeBusinessGroups()
                    ->wherePivot('can_assign_cross_business_tasks', true)
                    ->pluck('business_groups.id');
                
                if ($groupIds->isNotEmpty()) {
                    // Check if assigned user's business is in any of these groups
                    $assignedUserInGroup = DB::table('business_group_memberships')
                        ->whereIn('business_group_id', $groupIds)
                        ->where('business_id', $assignedUser->employee->business_id)
                        ->where('status', 'active')
                        ->exists();
                    
                    if ($assignedUserInGroup) {
                        $canAssign = true;
                        Log::info('Cross-business assignment via group', [
                            'creator_business_id' => $currentUserEmployee->business_id,
                            'assignee_business_id' => $assignedUser->employee->business_id
                        ]);
                    }
                }
            }
            
            if (!$canAssign) {
                Log::warning('Cross-business assignment not allowed', [
                    'current_user_business_id' => $currentUserEmployee->business_id,
                    'assigned_user_business_id' => $assignedUser->employee->business_id
                ]);
                return response()->json([
                    'message' => 'You can only assign tasks to employees within your business or connected business groups'
                ], 403);
            }

            Log::info('Creating task', [
                'title' => $validated['title'],
                'created_by' => $currentUser->id,
                'assigned_to' => $validated['assigned_to'],
                'creator_business_id' => $currentUserEmployee->business_id,
                'assignee_business_id' => $assignedUser->employee->business_id
            ]);

            // Create the task
            $task = Task::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'priority' => $validated['priority'],
                'assigned_to' => $validated['assigned_to'],
                'deadline' => $validated['deadline'],
                'created_by' => $currentUser->id,
                'status' => 'todo',
            ]);

            Log::info('Task created successfully', [
                'task_id' => $task->id,
                'assigned_to_user_id' => $task->assigned_to,
                'assigned_to_employee_id' => $assignedUser->employee->employee_id,
                'created_by' => $task->created_by,
                'created_by_role' => $currentUser->role,
                'creator_business_id' => $currentUserEmployee->business_id,
                'assignee_business_id' => $assignedUser->employee->business_id
            ]);

            // ✨ CREATE PUSH NOTIFICATION
            UserNotification::create([
                'user_id' => $task->assigned_to,
                'type' => 'task_assigned',
                'title' => 'New Task Assigned',
                'message' => "{$currentUser->first_name} {$currentUser->last_name} assigned you a task: {$task->title}",
                'action' => "/tasks/{$task->id}",
                'data' => [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'priority' => $task->priority,
                    'deadline' => $task->deadline,
                    'assigned_by' => $currentUser->first_name . ' ' . $currentUser->last_name,
                ]
            ]);
            // Push notification is automatically sent via UserNotification boot method!

            // Dispatch event to trigger other notifications (email, etc.)
            event(new TaskAssigned($task));

            return response()->json([
                'message' => 'Task created successfully',
                'task' => $task->load('assignedTo.employee.business', 'createdBy.employee.business', 'comments')
            ], 201);
           
        } catch (\Exception $e) {
            Log::error('Failed to create task', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
           
            return response()->json([
                'message' => 'Failed to create task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Task $task)
    {
        try {
            $user = Auth::user();
            
            // Authorization check
            $canView = false;
            
            // Anyone can view tasks they created or are assigned to
            if ($task->created_by === $user->id || $task->assigned_to === $user->id) {
                $canView = true;
            } else {
                // Check if users are in the same business or connected via groups
                $userEmployee = $user->employee;
                $taskAssignedEmployee = $task->assignedTo->employee ?? null;
                $taskCreatorEmployee = $task->createdBy->employee ?? null;
                
                if ($userEmployee && $userEmployee->business_id) {
                    // Same business check
                    if ($taskAssignedEmployee && $taskAssignedEmployee->business_id === $userEmployee->business_id) {
                        $canView = true;
                    }
                    if ($taskCreatorEmployee && $taskCreatorEmployee->business_id === $userEmployee->business_id) {
                        $canView = true;
                    }
                    
                    // Cross-business check via groups
                    if (!$canView) {
                        $business = $userEmployee->business;
                        $groupIds = $business->activeBusinessGroups()
                            ->wherePivot('can_assign_cross_business_tasks', true)
                            ->pluck('business_groups.id');
                        
                        if ($groupIds->isNotEmpty()) {
                            // Check if task's assigned user or creator is in a connected business
                            $connectedBusinessIds = DB::table('business_group_memberships')
                                ->whereIn('business_group_id', $groupIds)
                                ->where('status', 'active')
                                ->pluck('business_id')
                                ->toArray();
                            
                            if ($taskAssignedEmployee && in_array($taskAssignedEmployee->business_id, $connectedBusinessIds)) {
                                $canView = true;
                            }
                            if ($taskCreatorEmployee && in_array($taskCreatorEmployee->business_id, $connectedBusinessIds)) {
                                $canView = true;
                            }
                        }
                    }
                }
            }
            
            if (!$canView) {
                Log::warning('Unauthorized task view attempt', [
                    'user_id' => $user->id,
                    'task_id' => $task->id,
                    'task_created_by' => $task->created_by,
                    'task_assigned_to' => $task->assigned_to
                ]);
                return response()->json([
                    'message' => 'Unauthorized to view this task'
                ], 403);
            }

            return response()->json([
                'task' => $task->load('assignedTo.employee.business', 'createdBy.employee.business', 'comments', 'subtasks', 'history', 'workLogs', 'linkedItems')
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch task', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch task'], 500);
        }
    }

     public function update(Request $request, Task $task)
    {
        try {
            $user = Auth::user();
            
            Log::info('Task update attempt', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'task_id' => $task->id,
                'task_created_by' => $task->created_by,
                'task_assigned_to' => $task->assigned_to
            ]);
            
            // Authorization: Only task creator, assigned user, or admin/manager in same business can update
            $canUpdate = false;
            
            // Task creator can always update
            if ($task->created_by === $user->id) {
                $canUpdate = true;
            }
            // Assigned user can update if they are assigned to the task
            else if ($task->assigned_to === $user->id) {
                $canUpdate = true;
            }
            // Admin/Manager in same business can update
            else {
                $userEmployee = $user->employee;
                $taskCreatorEmployee = $task->createdBy->employee ?? null;
                
                if ($userEmployee && $taskCreatorEmployee && 
                    $userEmployee->business_id === $taskCreatorEmployee->business_id &&
                    in_array($user->role, ['admin', 'manager'])) {
                    $canUpdate = true;
                }
            }
            
            if (!$canUpdate) {
                Log::warning('Unauthorized task update attempt', [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'task_id' => $task->id
                ]);
                return response()->json([
                    'message' => 'Unauthorized to update this task'
                ], 403);
            }

            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|nullable|string',
                'priority' => 'sometimes|in:low,moderate,high,critical',
                'assigned_to' => 'sometimes|exists:users,id',
                'deadline' => 'sometimes|nullable|date',
                'status' => 'sometimes|in:todo,in_progress,under_review,completed',
            ]);

            // Track what changed
            $changes = [];
            foreach ($validated as $key => $value) {
                if ($task->$key != $value) {
                    $changes[$key] = [
                        'old' => $task->$key,
                        'new' => $value
                    ];
                }
            }

            // If updating assigned_to, verify employee record exists and assignment is allowed
            if (isset($validated['assigned_to']) && $validated['assigned_to'] != $task->assigned_to) {
                $assignedUser = User::with('employee.business')->find($validated['assigned_to']);
             
                if (!$assignedUser || !$assignedUser->employee) {
                    return response()->json([
                        'message' => 'Assigned user must have an employee record'
                    ], 400);
                }
                
                // Verify assignment is allowed
                $userEmployee = $user->employee;
                $canAssign = false;
                
                if ($userEmployee && $assignedUser->employee->business_id === $userEmployee->business_id) {
                    $canAssign = true;
                } else if ($userEmployee) {
                    // Check cross-business permission
                    $business = $userEmployee->business;
                    $groupIds = $business->activeBusinessGroups()
                        ->wherePivot('can_assign_cross_business_tasks', true)
                        ->pluck('business_groups.id');
                    
                    if ($groupIds->isNotEmpty()) {
                        $assignedUserInGroup = DB::table('business_group_memberships')
                            ->whereIn('business_group_id', $groupIds)
                            ->where('business_id', $assignedUser->employee->business_id)
                            ->where('status', 'active')
                            ->exists();
                        
                        if ($assignedUserInGroup) {
                            $canAssign = true;
                        }
                    }
                }
                
                if (!$canAssign) {
                    return response()->json([
                        'message' => 'You can only assign tasks to employees within your business or connected business groups'
                    ], 403);
                }

                // ✨ SEND NOTIFICATION TO NEW ASSIGNEE
                UserNotification::create([
                    'user_id' => $validated['assigned_to'],
                    'type' => 'task_assigned',
                    'title' => 'Task Reassigned to You',
                    'message' => "{$user->first_name} {$user->last_name} assigned you a task: {$task->title}",
                    'action' => "/tasks/{$task->id}",
                    'data' => [
                        'task_id' => $task->id,
                        'task_title' => $task->title,
                        'priority' => $task->priority,
                        'reassigned_by' => $user->first_name . ' ' . $user->last_name,
                    ]
                ]);
            }

            // If status changed to completed
            if (isset($validated['status']) && $validated['status'] === 'completed' && $task->status !== 'completed') {
                // Notify task creator
                if ($task->created_by !== $user->id) {
                    UserNotification::create([
                        'user_id' => $task->created_by,
                        'type' => 'task_assigned', // Using task_assigned type for consistency
                        'title' => 'Task Completed',
                        'message' => "{$user->first_name} {$user->last_name} completed the task: {$task->title}",
                        'action' => "/tasks/{$task->id}",
                        'data' => [
                            'task_id' => $task->id,
                            'task_title' => $task->title,
                            'completed_by' => $user->first_name . ' ' . $user->last_name,
                        ]
                    ]);
                }
            }

            $task->update($validated);

            Log::info('Task updated', [
                'task_id' => $task->id,
                'updated_by' => $user->id,
                'updated_by_role' => $user->role,
                'updated_fields' => array_keys($validated),
                'changes' => $changes
            ]);

            return response()->json([
                'message' => 'Task updated successfully',
                'task' => $task->fresh(['assignedTo.employee.business', 'createdBy.employee.business', 'comments'])
            ]);
           
        } catch (\Exception $e) {
            Log::error('Failed to update task', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Failed to update task'], 500);
        }
    }

    public function updateStatus(Request $request, Task $task)
    {
        try {
            $user = Auth::user();
            
            Log::info('Updating task status', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'task_id' => $task->id,
                'task_assigned_to' => $task->assigned_to,
                'task_created_by' => $task->created_by,
                'new_status' => $request->status
            ]);

            // Authorization: Assigned employee, task creator, or admin/manager in same business
            $canUpdateStatus = false;
            
            if ($task->assigned_to === $user->id || $task->created_by === $user->id) {
                $canUpdateStatus = true;
            } else {
                // Check if user is in the same business and is admin/manager
                $userEmployee = $user->employee;
                $taskCreatorEmployee = $task->createdBy->employee ?? null;
                
                if ($userEmployee && $taskCreatorEmployee && 
                    $userEmployee->business_id === $taskCreatorEmployee->business_id &&
                    in_array($user->role, ['admin', 'manager'])) {
                    $canUpdateStatus = true;
                }
            }
            
            if (!$canUpdateStatus) {
                Log::warning('Unauthorized status update attempt', [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'task_id' => $task->id
                ]);
                return response()->json([
                    'message' => 'Unauthorized to update task status'
                ], 403);
            }

            $validated = $request->validate([
                'status' => 'required|in:todo,in_progress,under_review,completed'
            ]);

            $task->update(['status' => $validated['status']]);

            Log::info('Task status updated successfully', [
                'task_id' => $task->id,
                'new_status' => $task->status,
                'updated_by' => $user->id,
                'updated_by_role' => $user->role
            ]);

            return response()->json([
                'message' => 'Task status updated successfully',
                'task' => $task->fresh(['assignedTo.employee.business', 'createdBy.employee.business', 'comments'])
            ]);
           
        } catch (\Exception $e) {
            Log::error('Failed to update task status', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update task status'], 500);
        }
    }

    public function destroy(Task $task)
    {
        try {
            $user = Auth::user();
            
            Log::info('Task deletion attempt', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'task_id' => $task->id,
                'task_created_by' => $task->created_by
            ]);
            
            // Authorization: Only task creator or admin can delete
            if ($task->created_by !== $user->id && $user->role !== 'admin') {
                Log::warning('Unauthorized task deletion attempt', [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'task_created_by' => $task->created_by
                ]);
                return response()->json([
                    'message' => 'Unauthorized to delete this task. Only the task creator or admin can delete it.'
                ], 403);
            }
           
            $task->delete();

            Log::info('Task deleted', [
                'task_id' => $task->id,
                'deleted_by' => $user->id,
                'deleted_by_role' => $user->role
            ]);

            return response()->json([
                'message' => 'Task deleted successfully'
            ]);
           
        } catch (\Exception $e) {
            Log::error('Failed to delete task', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to delete task'], 500);
        }
    }

    /**
     * Get simple employee list for task assignment dropdown
     * UPDATED: Includes users from connected business groups
     */
    public function getSimpleEmployees()
    {
        try {
            $currentUser = Auth::user();
            $userEmployee = $currentUser->employee;
           
            if (!$userEmployee || !$userEmployee->business_id) {
                Log::warning('User has no business association', [
                    'user_id' => $currentUser->id,
                    'user_role' => $currentUser->role
                ]);
                
                // Return current user only if no business
                $employees = collect([$currentUser])->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'full_name' => $user->first_name . ' ' . $user->last_name . ' (You)',
                        'email' => $user->email,
                        'employee_id' => $user->employee->employee_id ?? null,
                        'position' => $user->employee->position ?? null,
                        'department' => $user->employee->department ?? null,
                        'is_self' => true,
                        'role' => $user->role,
                        'business_id' => null,
                        'business_name' => null,
                        'is_from_other_business' => false
                    ];
                });
            } else {
                $business = $userEmployee->business;
                
                // Get employees from current business
                $currentBusinessEmployees = Employee::with('user')
                    ->where('business_id', $userEmployee->business_id)
                    ->get()
                    ->filter(function ($employee) {
                        return $employee->user !== null;
                    })
                    ->map(function ($employee) use ($currentUser) {
                        $isSelf = $employee->user_id === $currentUser->id;
                        return [
                            'id' => $employee->user->id,
                            'first_name' => $employee->user->first_name,
                            'last_name' => $employee->user->last_name,
                            'full_name' => $employee->full_name . ($isSelf ? ' (You)' : ''),
                            'email' => $employee->user->email,
                            'employee_id' => $employee->employee_id,
                            'position' => $employee->position,
                            'department' => $employee->department,
                            'is_self' => $isSelf,
                            'role' => $employee->user->role,
                            'business_id' => $employee->business_id,
                            'business_name' => $employee->business->name ?? null,
                            'is_from_other_business' => false
                        ];
                    });
                
                // Get business groups with cross-business task permission
                $groupIds = $business->activeBusinessGroups()
                    ->wherePivot('can_assign_cross_business_tasks', true)
                    ->pluck('business_groups.id');
                
                $groupBusinessEmployees = collect([]);
                
                if ($groupIds->isNotEmpty()) {
                    // Get other business IDs in the same groups
                    $otherBusinessIds = DB::table('business_group_memberships')
                        ->whereIn('business_group_id', $groupIds)
                        ->where('business_id', '!=', $userEmployee->business_id)
                        ->where('status', 'active')
                        ->pluck('business_id');
                    
                    if ($otherBusinessIds->isNotEmpty()) {
                        $groupBusinessEmployees = Employee::with(['user', 'business'])
                            ->whereIn('business_id', $otherBusinessIds)
                            ->get()
                            ->filter(function ($employee) {
                                return $employee->user !== null;
                            })
                            ->map(function ($employee) {
                                return [
                                    'id' => $employee->user->id,
                                    'first_name' => $employee->user->first_name,
                                    'last_name' => $employee->user->last_name,
                                    'full_name' => $employee->full_name . ' (' . ($employee->business->name ?? 'External') . ')',
                                    'email' => $employee->user->email,
                                    'employee_id' => $employee->employee_id,
                                    'position' => $employee->position,
                                    'department' => $employee->department,
                                    'is_self' => false,
                                    'role' => $employee->user->role,
                                    'business_id' => $employee->business_id,
                                    'business_name' => $employee->business->name ?? null,
                                    'is_from_other_business' => true
                                ];
                            });
                    }
                }
                
                // Merge both collections
                $employees = $currentBusinessEmployees->merge($groupBusinessEmployees)->values();
                
                Log::info('TASK_CONTROLLER: Employee list fetched with cross-business support', [
                    'current_business_employees' => $currentBusinessEmployees->count(),
                    'group_business_employees' => $groupBusinessEmployees->count(),
                    'total_employees' => $employees->count()
                ]);
            }

            Log::info('TASK_CONTROLLER: Simple employee list fetched', [
                'user_id' => $currentUser->id,
                'user_role' => $currentUser->role,
                'business_id' => $userEmployee->business_id ?? null,
                'employee_count' => $employees->count()
            ]);

            return response()->json(['employees' => $employees]);
        } catch (\Exception $e) {
            Log::error('TASK_CONTROLLER: Failed to fetch simple employee list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to fetch employees',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employees for task assignment
     */
    public function getEmployees(): AnonymousResourceCollection
    {
        try {
            $currentUser = Auth::user();
            $userEmployee = $currentUser->employee;
           
            if (!$userEmployee || !$userEmployee->business_id) {
                Log::warning('User has no business association', [
                    'user_id' => $currentUser->id,
                    'user_role' => $currentUser->role
                ]);
                
                // Return empty collection if no business
                $employees = collect([]);
            } else {
                // Get all employees in the same business
                $employees = Employee::with(['user', 'manager'])
                    ->where('business_id', $userEmployee->business_id)
                    ->where('user_id', '!=', $currentUser->id) // Exclude self
                    ->get();
            }

            Log::info('TASK_CONTROLLER: Employees fetched', [
                'user_id' => $currentUser->id,
                'user_role' => $currentUser->role,
                'business_id' => $userEmployee->business_id ?? null,
                'count' => $employees->count()
            ]);

            return EmployeeResource::collection($employees);
        } catch (\Exception $e) {
            Log::error('Failed to fetch employees', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}