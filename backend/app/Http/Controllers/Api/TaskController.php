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
           
            if ($user->role === 'manager') {
                // Managers see all tasks they created
                $tasks = Task::with([
                'assignedTo.employee', 
                'createdBy', 
                'comments.user',
                'subtasks.assignee',
                'workLogs.user',
                'history.user',
                'linkedItems.linkedTask'
            ])
                ->createdByUser($user->id)
                ->orderBy('deadline', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();
                  
                Log::info('Manager tasks fetched', ['count' => $tasks->count()]);
            } elseif ($user->role === 'admin') {
                // Admins see all tasks within their business
                $userEmployee = $user->employee;
                
                if (!$userEmployee || !$userEmployee->business_id) {
                    Log::warning('Admin user has no business association', [
                        'user_id' => $user->id
                    ]);
                    
                    // Fallback: show tasks created by admin
                    $tasks = Task::with(['assignedTo.employee', 'createdBy', 'comments'])
                        ->createdByUser($user->id)
                        ->orderBy('deadline', 'asc')
                        ->orderBy('created_at', 'desc')
                        ->get();
                } else {
                    // Get all user IDs for employees within the same business
                    $businessEmployeeUserIds = Employee::where('business_id', $userEmployee->business_id)
                        ->pluck('user_id')
                        ->toArray();
                    
                    Log::info('Admin business employees', [
                        'business_id' => $userEmployee->business_id,
                        'employee_user_ids' => $businessEmployeeUserIds
                    ]);
                    
                    // Get all tasks where EITHER:
                    // 1. Task is assigned to any employee in the business, OR
                    // 2. Task was created by any employee in the business
                    $tasks = Task::with(['assignedTo.employee', 'createdBy', 'comments'])
                        ->where(function($query) use ($businessEmployeeUserIds) {
                            $query->whereIn('assigned_to', $businessEmployeeUserIds)
                                  ->orWhereIn('created_by', $businessEmployeeUserIds);
                        })
                        ->orderBy('deadline', 'asc')
                        ->orderBy('created_at', 'desc')
                        ->get();
                }
                
                Log::info('Admin tasks fetched', [
                    'count' => $tasks->count(),
                    'business_id' => $userEmployee->business_id ?? null
                ]);
            } else {
                // Employees see tasks assigned to them based on their user_id
                $tasks = Task::with(['assignedTo.employee', 'createdBy', 'comments'])
                    ->forUser($user->id)
                    ->orderBy('deadline', 'asc')
                    ->orderBy('created_at', 'desc')
                    ->get();
                  
                Log::info('Employee tasks fetched from database', [
                    'user_id' => $user->id,
                    'count' => $tasks->count(),
                ]);
            }

            // Format tasks with additional employee info
            $formattedTasks = $tasks->map(function ($task) {
                $assignedUserEmployee = $task->assignedTo->employee ?? null;
             
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
                    ],
                    'created_by' => [
                        'id' => $task->createdBy->id,
                        'first_name' => $task->createdBy->first_name,
                        'last_name' => $task->createdBy->last_name,
                        'email' => $task->createdBy->email,
                        'name' => $task->createdBy->first_name . ' ' . $task->createdBy->last_name,
                    ],
                    'comments' => $task->comments,
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

            // Verify the assigned user has an employee record
            $assignedUser = User::with('employee')->find($validated['assigned_to']);
           
            if (!$assignedUser) {
                return response()->json([
                    'message' => 'Assigned user not found'
                ], 404);
            }

            if (!$assignedUser->employee) {
                return response()->json([
                    'message' => 'Assigned user does not have an employee record'
                ], 400);
            }

            // For admins, verify the assigned user is in the same business
            if ($currentUser->role === 'admin') {
                $currentUserEmployee = $currentUser->employee;
                
                if ($currentUserEmployee && $currentUserEmployee->business_id) {
                    if ($assignedUser->employee->business_id !== $currentUserEmployee->business_id) {
                        return response()->json([
                            'message' => 'You can only assign tasks to employees within your business'
                        ], 403);
                    }
                }
            }

            // For managers, verify the assigned user is under their management
            if ($currentUser->role === 'manager') {
                $isUnderManagement = Employee::where('user_id', $validated['assigned_to'])
                    ->where('manager_id', $currentUser->id)
                    ->exists();
                
                if (!$isUnderManagement) {
                    return response()->json([
                        'message' => 'You can only assign tasks to employees under your management'
                    ], 403);
                }
            }

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

            Log::info('Task created', [
                'task_id' => $task->id,
                'assigned_to_user_id' => $task->assigned_to,
                'assigned_to_employee_id' => $assignedUser->employee->employee_id,
                'created_by' => $task->created_by,
                'created_by_role' => $currentUser->role
            ]);

            // Dispatch event to trigger notification
            event(new TaskAssigned($task));

            return response()->json([
                'message' => 'Task created successfully',
                'task' => $task->load('assignedTo.employee', 'createdBy', 'comments')
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
            if ($user->role === 'employee') {
                // Employees can only view their own tasks
                if ($task->assigned_to !== $user->id) {
                    return response()->json([
                        'message' => 'Unauthorized to view this task'
                    ], 403);
                }
            } elseif ($user->role === 'manager') {
                // Managers can view tasks they created
                if ($task->created_by !== $user->id && $task->assigned_to !== $user->id) {
                    return response()->json([
                        'message' => 'Unauthorized to view this task'
                    ], 403);
                }
            } elseif ($user->role === 'admin') {
                // Admins can view tasks within their business
                $userEmployee = $user->employee;
                
                if ($userEmployee && $userEmployee->business_id) {
                    // Check if either the assigned user or creator is in the same business
                    $taskAssignedEmployee = $task->assignedTo->employee ?? null;
                    $taskCreatorEmployee = $task->createdBy->employee ?? null;
                    
                    $canView = false;
                    
                    if ($taskAssignedEmployee && $taskAssignedEmployee->business_id === $userEmployee->business_id) {
                        $canView = true;
                    }
                    
                    if ($taskCreatorEmployee && $taskCreatorEmployee->business_id === $userEmployee->business_id) {
                        $canView = true;
                    }
                    
                    if (!$canView) {
                        return response()->json([
                            'message' => 'Unauthorized to view this task'
                        ], 403);
                    }
                }
            }

            return response()->json([
                'task' => $task->load('assignedTo.employee', 'createdBy', 'comments')
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
            
            // Authorization: Only task creator or admin in same business can update
            $canUpdate = false;
            
            if ($task->created_by === $user->id) {
                $canUpdate = true;
            } elseif ($user->role === 'admin') {
                $userEmployee = $user->employee;
                
                if ($userEmployee && $userEmployee->business_id) {
                    $taskAssignedEmployee = $task->assignedTo->employee ?? null;
                    $taskCreatorEmployee = $task->createdBy->employee ?? null;
                    
                    // Admin can update if task involves anyone in their business
                    if (($taskAssignedEmployee && $taskAssignedEmployee->business_id === $userEmployee->business_id) ||
                        ($taskCreatorEmployee && $taskCreatorEmployee->business_id === $userEmployee->business_id)) {
                        $canUpdate = true;
                    }
                }
            }
            
            if (!$canUpdate) {
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

            // If updating assigned_to, verify employee record exists and business match
            if (isset($validated['assigned_to'])) {
                $assignedUser = User::with('employee')->find($validated['assigned_to']);
             
                if (!$assignedUser || !$assignedUser->employee) {
                    return response()->json([
                        'message' => 'Assigned user must have an employee record'
                    ], 400);
                }
                
                // Verify business match for admins
                if ($user->role === 'admin' && $user->employee) {
                    if ($assignedUser->employee->business_id !== $user->employee->business_id) {
                        return response()->json([
                            'message' => 'You can only assign tasks to employees within your business'
                        ], 403);
                    }
                }
            }

            $task->update($validated);

            Log::info('Task updated', [
                'task_id' => $task->id,
                'updated_by' => $user->id,
                'updated_by_role' => $user->role,
                'updated_fields' => array_keys($validated)
            ]);

            return response()->json([
                'message' => 'Task updated successfully',
                'task' => $task->fresh(['assignedTo.employee', 'createdBy', 'comments'])
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

            // Authorization: Assigned employee, task creator, or admin in same business
            $canUpdateStatus = false;
            
            if ($task->assigned_to === $user->id || $task->created_by === $user->id) {
                $canUpdateStatus = true;
            } elseif ($user->role === 'admin') {
                $userEmployee = $user->employee;
                
                if ($userEmployee && $userEmployee->business_id) {
                    $taskAssignedEmployee = $task->assignedTo->employee ?? null;
                    $taskCreatorEmployee = $task->createdBy->employee ?? null;
                    
                    // Admin can update if task involves anyone in their business
                    if (($taskAssignedEmployee && $taskAssignedEmployee->business_id === $userEmployee->business_id) ||
                        ($taskCreatorEmployee && $taskCreatorEmployee->business_id === $userEmployee->business_id)) {
                        $canUpdateStatus = true;
                    }
                }
            }
            
            if (!$canUpdateStatus) {
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
                'task' => $task->fresh(['assignedTo.employee', 'createdBy', 'comments'])
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
            
            // Authorization: Only task creator or admin in same business can delete
            $canDelete = false;
            
            if ($task->created_by === $user->id) {
                $canDelete = true;
            } elseif ($user->role === 'admin') {
                $userEmployee = $user->employee;
                
                if ($userEmployee && $userEmployee->business_id) {
                    $taskAssignedEmployee = $task->assignedTo->employee ?? null;
                    $taskCreatorEmployee = $task->createdBy->employee ?? null;
                    
                    // Admin can delete if task involves anyone in their business
                    if (($taskAssignedEmployee && $taskAssignedEmployee->business_id === $userEmployee->business_id) ||
                        ($taskCreatorEmployee && $taskCreatorEmployee->business_id === $userEmployee->business_id)) {
                        $canDelete = true;
                    }
                }
            }
            
            if (!$canDelete) {
                return response()->json([
                    'message' => 'Unauthorized to delete this task'
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
     * Get employees for task assignment
     */
    public function getEmployees(): AnonymousResourceCollection
    {
        try {
            $currentUser = Auth::user();
           
            if ($currentUser->role === 'manager') {
                // Get employees managed by this manager
                $employees = Employee::with(['user', 'manager'])
                    ->where('manager_id', $currentUser->id)
                    ->get();
            } elseif ($currentUser->role === 'admin') {
                // Admins can see all employees in their business
                $userEmployee = $currentUser->employee;
                
                if ($userEmployee && $userEmployee->business_id) {
                    $employees = Employee::with(['user', 'manager'])
                        ->where('business_id', $userEmployee->business_id)
                        ->get();
                } else {
                    // Fallback: get all employees
                    $employees = Employee::with(['user', 'manager'])->get();
                }
            } else {
                // Regular employees see no one
                $employees = collect([]);
            }

            Log::info('TASK_CONTROLLER: Employees fetched', [
                'user_id' => $currentUser->id,
                'user_role' => $currentUser->role,
                'count' => $employees->count()
            ]);

            return EmployeeResource::collection($employees);
        } catch (\Exception $e) {
            Log::error('Failed to fetch employees', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get simple employee list for task assignment dropdown
     * Returns user_id as 'id' for task assignment
     */
    public function getSimpleEmployees()
    {
        try {
            $currentUser = Auth::user();
           
            if ($currentUser->role === 'manager') {
                // Get employees under this manager
                $employees = Employee::with('user')
                    ->where('manager_id', $currentUser->id)
                    ->get()
                    ->filter(function ($employee) {
                        return $employee->user !== null;
                    })
                    ->map(function ($employee) {
                        return [
                            'id' => $employee->user->id,
                            'first_name' => $employee->user->first_name,
                            'last_name' => $employee->user->last_name,
                            'full_name' => $employee->full_name,
                            'email' => $employee->user->email,
                            'employee_id' => $employee->employee_id,
                            'position' => $employee->position,
                            'department' => $employee->department
                        ];
                    })
                    ->values();
            } elseif ($currentUser->role === 'admin') {
                // Admins can see all employees in their business
                $userEmployee = $currentUser->employee;
                
                if ($userEmployee && $userEmployee->business_id) {
                    $employees = Employee::with('user')
                        ->where('business_id', $userEmployee->business_id)
                        ->get()
                        ->filter(function ($employee) {
                            return $employee->user !== null;
                        })
                        ->map(function ($employee) {
                            return [
                                'id' => $employee->user->id,
                                'first_name' => $employee->user->first_name,
                                'last_name' => $employee->user->last_name,
                                'full_name' => $employee->full_name,
                                'email' => $employee->user->email,
                                'employee_id' => $employee->employee_id,
                                'position' => $employee->position,
                                'department' => $employee->department
                            ];
                        })
                        ->values();
                } else {
                    // Fallback: get all employees
                    $employees = Employee::with('user')
                        ->get()
                        ->filter(function ($employee) {
                            return $employee->user !== null;
                        })
                        ->map(function ($employee) {
                            return [
                                'id' => $employee->user->id,
                                'first_name' => $employee->user->first_name,
                                'last_name' => $employee->user->last_name,
                                'full_name' => $employee->full_name,
                                'email' => $employee->user->email,
                                'employee_id' => $employee->employee_id,
                                'position' => $employee->position,
                                'department' => $employee->department
                            ];
                        })
                        ->values();
                }
            } else {
                // Regular employees see no one
                $employees = collect([]);
            }

            Log::info('TASK_CONTROLLER: Simple employee list fetched', [
                'user_id' => $currentUser->id,
                'user_role' => $currentUser->role,
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
}