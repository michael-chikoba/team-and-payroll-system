<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class TaskCommentController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $this->authorize('view', $task);

        $validated = $request->validate([
            'comment' => 'required|string'
        ]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'comment' => $validated['comment']
        ]);

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $comment->load('user')
        ], 201);
    }

    public function destroy(TaskComment $comment)
    {
        if ($comment->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully'
        ]);
    }
}