<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\BusinessGroup;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class GroupTaskController extends Controller
{
    /**
     * Get all tasks across business group
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $business = $this->getCurrentBusiness($user);

            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'No business association found'
                ], 404);
            }

            // Get business groups with task permissions
            $groupIds = $business->activeBusinessGroups()
                ->wherePivot('can_assign_cross_business_tasks', true)
                ->pluck('business_groups.id');

            if ($groupIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $query = Task::with(['assignedTo', 'createdBy', 'businessGroup'])
                ->whereIn('business_group_id', $groupIds)
                ->where('is_group_task', true);

            // Apply filters
            if ($request->has('business_group_id')) {
                $query->where('business_group_id', $request->business_group_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            $tasks = $query->latest()->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $tasks
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching group tasks', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tasks'
            ], 500);
        }
    }

    /**
     * Create cross-business task
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'business_group_id' => 'required|exists:business_groups,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,moderate,high,critical',
            'assigned_to' => 'required|exists:users,id',
            'assigned_business_id' => 'nullable|exists:businesses,id',
            'deadline' => 'nullable|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = $request->user();
            $business = $this->getCurrentBusiness($user);

            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'No business association found'
                ], 404);
            }

            $businessGroup = BusinessGroup::find($request->business_group_id);

            // Check permissions
            if (!$business->canAssignCrossBusinessTasks($businessGroup->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to create cross-business tasks'
                ], 403);
            }

            if (!$businessGroup->allow_cross_business_tasks) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cross-business tasks are not enabled for this group'
                ], 403);
            }

            // Create task
            $task = Task::create([
                'business_group_id' => $businessGroup->id,
                'is_group_task' => true,
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority,
                'assigned_to' => $request->assigned_to,
                'assigned_business_id' => $request->assigned_business_id,
                'created_by' => $user->id,
                'deadline' => $request->deadline,
                'status' => 'todo',
            ]);

            $businessGroup->logActivity(
                'group_task_created',
                "Group task '{$task->title}' created",
                $business->id,
                $user->id,
                ['task_id' => $task->id]
            );

            DB::commit();

            $task->load(['assignedTo', 'createdBy', 'businessGroup']);

            return response()->json([
                'success' => true,
                'message' => 'Group task created successfully',
                'data' => $task
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creating group task', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task'
            ], 500);
        }
    }

    /**
     * Show task details
     */
    public function show(Task $task, Request $request): JsonResponse
    {
        $user = $request->user();
        $business = $this->getCurrentBusiness($user);

        if (!$this->canViewTask($task, $business)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $task->load(['assignedTo', 'createdBy', 'businessGroup', 'subtasks', 'comments']);

        return response()->json([
            'success' => true,
            'data' => $task
        ]);
    }

    /**
     * Update task
     */
    public function update(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();
        $business = $this->getCurrentBusiness($user);

        if (!$this->canEditTask($task, $business, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'priority' => 'sometimes|in:low,moderate,high,critical',
            'status' => 'sometimes|in:todo,in_progress,under_review,completed',
            'deadline' => 'nullable|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $task->update($request->only([
                'title',
                'description',
                'priority',
                'status',
                'deadline'
            ]));

            if ($task->businessGroup) {
                $task->businessGroup->logActivity(
                    'group_task_updated',
                    "Group task '{$task->title}' updated",
                    $business->id,
                    $user->id,
                    ['task_id' => $task->id]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully',
                'data' => $task->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating group task', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task'
            ], 500);
        }
    }

    /**
     * Assign task to a business
     */
    public function assignToBusiness(Request $request, Task $task): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|exists:businesses,id',
            'assigned_to' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $business = $this->getCurrentBusiness($user);

            if (!$business || !$business->canAssignCrossBusinessTasks($task->business_group_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            DB::beginTransaction();

            $task->update([
                'assigned_business_id' => $request->business_id,
                'assigned_to' => $request->assigned_to,
            ]);

            $task->businessGroup->logActivity(
                'task_assigned_to_business',
                "Task assigned to business ID: {$request->business_id}",
                $business->id,
                $user->id,
                ['task_id' => $task->id]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Task assigned successfully',
                'data' => $task->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error assigning task to business', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign task'
            ], 500);
        }
    }

    // Helper methods
    private function getCurrentBusiness($user): ?Business
    {
        $employee = $user->employee;
        return $employee ? $employee->business : null;
    }

    private function canViewTask(Task $task, ?Business $business): bool
    {
        if (!$business || !$task->is_group_task) {
            return false;
        }

        return $business->canAssignCrossBusinessTasks($task->business_group_id);
    }

    private function canEditTask(Task $task, ?Business $business, User $user): bool
    {
        if (!$this->canViewTask($task, $business)) {
            return false;
        }

        return $task->created_by === $user->id || $task->assigned_to === $user->id;
    }
}