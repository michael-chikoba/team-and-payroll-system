<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use App\Models\TaskLink;
use App\Models\TaskHistory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskLinkController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $logContext = [
            'user_id' => Auth::id(),
            'task_id' => $task->id,
            'endpoint' => 'store',
        ];

        try {
            // Log incoming request
            Log::info('TaskLink store request received', array_merge($logContext, [
                'request_data' => $request->all(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]));

            $validated = $request->validate([
                'linked_task_id' => 'required|exists:tasks,id',
                'link_type' => 'required|in:blocks,blocked_by,relates_to,duplicates,duplicated_by,parent_of,child_of',
            ]);

            // Prevent self-linking
            if ($task->id == $validated['linked_task_id']) {
                $response = response()->json(['message' => 'Cannot link task to itself'], 400);
                
                Log::warning('TaskLink self-linking attempt', array_merge($logContext, [
                    'response_status' => 400,
                    'response_data' => $response->getData(),
                ]));
                
                return $response;
            }

            // Check if link already exists
            $existingLink = TaskLink::where('task_id', $task->id)
                ->where('linked_task_id', $validated['linked_task_id'])
                ->where('link_type', $validated['link_type'])
                ->first();

            if ($existingLink) {
                $response = response()->json(['message' => 'Link already exists'], 400);
                
                Log::warning('TaskLink duplicate link attempt', array_merge($logContext, [
                    'linked_task_id' => $validated['linked_task_id'],
                    'link_type' => $validated['link_type'],
                    'response_status' => 400,
                    'response_data' => $response->getData(),
                ]));
                
                return $response;
            }

            $link = $task->links()->create([
                'linked_task_id' => $validated['linked_task_id'],
                'link_type' => $validated['link_type'],
                'created_by' => Auth::id(),
            ]);

            // Log successful link creation
            Log::info('TaskLink created', array_merge($logContext, [
                'link_id' => $link->id,
                'linked_task_id' => $validated['linked_task_id'],
                'link_type' => $validated['link_type'],
            ]));

            // Create reciprocal link for certain types
            $reciprocalTypes = [
                'blocks' => 'blocked_by',
                'blocked_by' => 'blocks',
                'parent_of' => 'child_of',
                'child_of' => 'parent_of',
                'duplicates' => 'duplicated_by',
                'duplicated_by' => 'duplicates',
            ];

            if (isset($reciprocalTypes[$validated['link_type']])) {
                $reciprocalLink = TaskLink::firstOrCreate([
                    'task_id' => $validated['linked_task_id'],
                    'linked_task_id' => $task->id,
                    'link_type' => $reciprocalTypes[$validated['link_type']],
                    'created_by' => Auth::id(),
                ]);

                Log::info('Reciprocal TaskLink created', array_merge($logContext, [
                    'reciprocal_link_id' => $reciprocalLink->id,
                    'reciprocal_link_type' => $reciprocalTypes[$validated['link_type']],
                ]));
            }

            // Log activity
            $linkedTask = Task::find($validated['linked_task_id']);
            TaskHistory::create([
                'task_id' => $task->id,
                'user_id' => Auth::id(),
                'type' => 'updated',
                'action' => "linked task {$validated['link_type']}: {$linkedTask->title}",
            ]);

            $response = response()->json([
                'message' => 'Task linked successfully',
                'link' => $link->load('linkedTask'),
            ], 201);

            // Log successful response
            Log::info('TaskLink store successful', array_merge($logContext, [
                'response_status' => 201,
                'linked_task_title' => $linkedTask->title,
            ]));

            return $response;

        } catch (\Exception $e) {
            Log::error('TaskLink store failed', array_merge($logContext, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]));

            return response()->json(['message' => 'Failed to link task'], 500);
        }
    }

    public function destroy(TaskLink $link, Request $request)
    {
        $logContext = [
            'user_id' => Auth::id(),
            'link_id' => $link->id,
            'task_id' => $link->task_id,
            'linked_task_id' => $link->linked_task_id,
            'link_type' => $link->link_type,
            'endpoint' => 'destroy',
        ];

        try {
            // Log incoming request
            Log::info('TaskLink destroy request received', array_merge($logContext, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]));

            $taskId = $link->task_id;
            $linkType = $link->link_type;
            $linkedTaskTitle = $link->linkedTask->title ?? 'Unknown';
            
            // Remove reciprocal link
            $reciprocalTypes = [
                'blocks' => 'blocked_by',
                'blocked_by' => 'blocks',
                'parent_of' => 'child_of',
                'child_of' => 'parent_of',
                'duplicates' => 'duplicated_by',
                'duplicated_by' => 'duplicates',
            ];

            if (isset($reciprocalTypes[$linkType])) {
                $deletedReciprocal = TaskLink::where('task_id', $link->linked_task_id)
                    ->where('linked_task_id', $link->task_id)
                    ->where('link_type', $reciprocalTypes[$linkType])
                    ->delete();

                Log::info('Reciprocal TaskLink removed', array_merge($logContext, [
                    'reciprocal_link_type' => $reciprocalTypes[$linkType],
                    'deleted_count' => $deletedReciprocal,
                ]));
            }

            $link->delete();

            Log::info('TaskLink removed', $logContext);

            // Log activity
            TaskHistory::create([
                'task_id' => $taskId,
                'user_id' => Auth::id(),
                'type' => 'updated',
                'action' => "removed link {$linkType}: {$linkedTaskTitle}",
            ]);

            $response = response()->json([
                'message' => 'Link removed successfully',
                'removed_link' => [
                    'id' => $link->id,
                    'task_id' => $taskId,
                    'linked_task_id' => $link->linked_task_id,
                    'link_type' => $linkType,
                ]
            ]);

            // Log successful response
            Log::info('TaskLink destroy successful', array_merge($logContext, [
                'response_status' => 200,
                'linked_task_title' => $linkedTaskTitle,
            ]));

            return $response;

        } catch (\Exception $e) {
            Log::error('TaskLink destroy failed', array_merge($logContext, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));

            return response()->json(['message' => 'Failed to remove link'], 500);
        }
    }
}