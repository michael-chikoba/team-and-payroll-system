<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use App\Models\User;
use App\Models\Employee;
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
                $tasks = Task::with(['assignedTo.employee', 'createdBy', 'comments'])
                    ->createdByUser($user->id)
                    ->orderBy('deadline', 'asc')
                    ->orderBy('created_at', 'desc')
                    ->get();
                  
                Log::info('Manager tasks fetched', ['count' => $tasks->count()]);
            } else {
                // Employees see tasks assigned to them based on their user_id
                // The assigned_to field in tasks table stores user_id
                $tasks = Task::with(['assignedTo.employee', 'createdBy', 'comments'])
                    ->forUser($user->id)
                    ->orderBy('deadline', 'asc')
                    ->orderBy('created_at', 'desc')
                    ->get();
                  
                Log::info('Employee tasks fetched from database', [
                    'user_id' => $user->id,
                    'count' => $tasks->count(),
                    'tasks_data' => $tasks->map(function ($task) {
                        return [
                            'id' => $task->id,
                            'title' => $task->title,
                            'description' => substr($task->description, 0, 100) . '...',
                            'priority' => $task->priority,
                            'status' => $task->status,
                            'assigned_to' => $task->assigned_to,
                            'created_by' => $task->created_by,
                            'deadline' => $task->deadline,
                            'created_at' => $task->created_at,
                            'updated_at' => $task->updated_at,
                        ];
                    })->toArray()
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
                        'name' => $task->assignedTo->first_name . ' ' . $task->assignedTo->last_name, // Added for frontend compatibility
                        'employee_id' => $assignedUserEmployee->employee_id ?? null,
                        'position' => $assignedUserEmployee->position ?? null,
                        'department' => $assignedUserEmployee->department ?? null,
                    ],
                    'created_by' => [
                        'id' => $task->createdBy->id,
                        'first_name' => $task->createdBy->first_name,
                        'last_name' => $task->createdBy->last_name,
                        'email' => $task->createdBy->email,
                        'name' => $task->createdBy->first_name . ' ' . $task->createdBy->last_name, // Added for frontend compatibility
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

            // Store user_id in assigned_to field (not employee_id)
            $task = Task::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'priority' => $validated['priority'],
                'assigned_to' => $validated['assigned_to'], // This is user_id
                'deadline' => $validated['deadline'],
                'created_by' => Auth::id(),
                'status' => 'todo',
            ]);

            Log::info('Task created', [
                'task_id' => $task->id,
                'assigned_to_user_id' => $task->assigned_to,
                'assigned_to_employee_id' => $assignedUser->employee->employee_id,
                'created_by' => $task->created_by
            ]);

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
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|nullable|string',
                'priority' => 'sometimes|in:low,moderate,high,critical',
                'assigned_to' => 'sometimes|exists:users,id',
                'deadline' => 'sometimes|nullable|date',
                'status' => 'sometimes|in:todo,in_progress,under_review,completed', // Added for potential status updates
            ]);

            // If updating assigned_to, verify employee record exists
            if (isset($validated['assigned_to'])) {
                $assignedUser = User::with('employee')->find($validated['assigned_to']);
             
                if (!$assignedUser || !$assignedUser->employee) {
                    return response()->json([
                        'message' => 'Assigned user must have an employee record'
                    ], 400);
                }
            }

            $task->update($validated);

            Log::info('Task updated', [
                'task_id' => $task->id,
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
            Log::info('Updating task status', [
                'user_id' => Auth::id(),
                'user_role' => Auth::user()->role,
                'task_id' => $task->id,
                'task_assigned_to' => $task->assigned_to,
                'task_created_by' => $task->created_by,
                'new_status' => $request->status
            ]);

            $validated = $request->validate([
                'status' => 'required|in:todo,in_progress,under_review,completed'
            ]);

            $task->update(['status' => $validated['status']]);

            Log::info('Task status updated successfully', [
                'task_id' => $task->id,
                'new_status' => $task->status
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
            $this->authorize('delete', $task);
           
            $task->delete();

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
            } else {
                // Admins can see all employees
                $employees = Employee::with(['user', 'manager'])->get();
            }

            Log::info('TASK_CONTROLLER: Employees fetched', [
                'manager_id' => $currentUser->id,
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
                        // Only include employees with valid user records
                        return $employee->user !== null;
                    })
                    ->map(function ($employee) {
                        return [
                            'id' => $employee->user->id, // user_id for task assignment
                            'first_name' => $employee->user->first_name,
                            'last_name' => $employee->user->last_name,
                            'full_name' => $employee->full_name,
                            'email' => $employee->user->email,
                            'employee_id' => $employee->employee_id,
                            'position' => $employee->position,
                            'department' => $employee->department
                        ];
                    })
                    ->values(); // Re-index array
            } else {
                // Admins can see all employees
                $employees = Employee::with('user')
                    ->get()
                    ->filter(function ($employee) {
                        return $employee->user !== null;
                    })
                    ->map(function ($employee) {
                        return [
                            'id' => $employee->user->id, // user_id for task assignment
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