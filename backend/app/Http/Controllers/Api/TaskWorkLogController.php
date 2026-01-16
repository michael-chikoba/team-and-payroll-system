<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use App\Models\TaskWorkLog;
use App\Models\TaskHistory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskWorkLogController extends Controller
{
    public function store(Request $request, Task $task)
    {
        try {
            $validated = $request->validate([
                'hours' => 'required|numeric|min:0.1|max:24',
                'description' => 'required|string',
                'work_date' => 'nullable|date',
            ]);

            $workLog = $task->workLogs()->create([
                'user_id' => Auth::id(),
                'hours' => $validated['hours'],
                'description' => $validated['description'],
                'work_date' => $validated['work_date'] ?? now(),
            ]);

            // Log activity
            TaskHistory::create([
                'task_id' => $task->id,
                'user_id' => Auth::id(),
                'type' => 'updated',
                'action' => "logged {$validated['hours']} hours of work",
            ]);

            return response()->json([
                'message' => 'Work log created successfully',
                'worklog' => $workLog->load('user'),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create work log', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to log time'], 500);
        }
    }

    public function destroy(TaskWorkLog $workLog)
    {
        try {
            $taskId = $workLog->task_id;
            $hours = $workLog->hours;
            
            $workLog->delete();

            // Log activity
            TaskHistory::create([
                'task_id' => $taskId,
                'user_id' => Auth::id(),
                'type' => 'updated',
                'action' => "removed work log entry ({$hours} hours)",
            ]);

            return response()->json(['message' => 'Work log deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to delete work log', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to delete work log'], 500);
        }
    }
}