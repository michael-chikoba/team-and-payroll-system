<?php

namespace App\Http\Controllers\Api;

use App\Models\Subtask;
use App\Models\Task;
use App\Models\TaskHistory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SubtaskController extends Controller
{
    public function store(Request $request, Task $task)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'priority' => 'required|in:low,medium,high',
                'assigned_to' => 'nullable|exists:users,id',
            ]);

            $subtask = $task->subtasks()->create([
                ...$validated,
                'created_by' => Auth::id(),
                'order' => $task->subtasks()->max('order') + 1,
            ]);

            // Log activity
            TaskHistory::create([
                'task_id' => $task->id,
                'user_id' => Auth::id(),
                'type' => 'updated',
                'action' => "added subtask: {$subtask->title}",
            ]);

            return response()->json([
                'message' => 'Subtask created successfully',
                'subtask' => $subtask->load('assignee'),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create subtask', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to create subtask'], 500);
        }
    }

    public function update(Request $request, Subtask $subtask)
    {
        try {
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|nullable|string',
                'priority' => 'sometimes|in:low,medium,high',
                'status' => 'sometimes|in:todo,in_progress,completed',
                'assigned_to' => 'sometimes|nullable|exists:users,id',
            ]);

            $oldStatus = $subtask->status;
            $subtask->update($validated);

            // Log status change
            if (isset($validated['status']) && $oldStatus !== $validated['status']) {
                TaskHistory::create([
                    'task_id' => $subtask->task_id,
                    'user_id' => Auth::id(),
                    'type' => 'updated',
                    'action' => "changed subtask '{$subtask->title}' status from {$oldStatus} to {$validated['status']}",
                ]);
            }

            return response()->json([
                'message' => 'Subtask updated successfully',
                'subtask' => $subtask->fresh('assignee'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update subtask', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update subtask'], 500);
        }
    }

    public function destroy(Subtask $subtask)
    {
        try {
            $taskId = $subtask->task_id;
            $title = $subtask->title;
            
            $subtask->delete();

            // Log activity
            TaskHistory::create([
                'task_id' => $taskId,
                'user_id' => Auth::id(),
                'type' => 'updated',
                'action' => "removed subtask: {$title}",
            ]);

            return response()->json(['message' => 'Subtask deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to delete subtask', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to delete subtask'], 500);
        }
    }
}