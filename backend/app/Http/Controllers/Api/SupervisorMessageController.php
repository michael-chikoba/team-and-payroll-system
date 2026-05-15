<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupervisorMessage;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\SupervisorMessageNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SupervisorMessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found', 'data' => []], 404);
        }

        try {
            [$employeeId, $supervisorUserId] = $this->resolveThread($user, $employee);

            if (!$supervisorUserId) {
                return response()->json([
                    'data' => [],
                    'message' => 'No supervisor assigned',
                ]);
            }

            $messages = SupervisorMessage::where('employee_id', $employeeId)
                ->where('supervisor_user_id', $supervisorUserId)
                ->with(['supervisor', 'sender'])
                ->orderBy('created_at', 'asc')
                ->get();

            // Auto-mark incoming messages as read
            SupervisorMessage::where('employee_id', $employeeId)
                ->where('supervisor_user_id', $supervisorUserId)
                ->where('sender_id', '!=', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            Log::info('SUPERVISOR_MSG: Thread fetched', [
                'user_id' => $user->id,
                'employee_id' => $employeeId,
                'supervisor_id' => $supervisorUserId,
                'message_count' => $messages->count(),
            ]);

            return response()->json(['data' => $messages]);

        } catch (\Exception $e) {
            Log::error('SUPERVISOR_MSG: Failed to fetch thread', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to load messages', 'data' => []], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found'], 404);
        }

        try {
            $validated = $request->validate([
                'message' => 'required|string|max:2000',
                'category' => 'nullable|string|in:leave,performance,payroll,workload,schedule,general,other',
            ]);

            [$employeeId, $supervisorUserId] = $this->resolveThread($user, $employee);

            if (!$supervisorUserId) {
                return response()->json(['message' => 'No supervisor assigned to your account'], 422);
            }

            DB::beginTransaction();

            $message = SupervisorMessage::create([
                'employee_id' => $employeeId,
                'supervisor_user_id' => $supervisorUserId,
                'sender_id' => $user->id,
                'message' => $validated['message'],
                'category' => $validated['category'] ?? null,
            ]);

            $message->load(['supervisor', 'sender']);

            // Notify the recipient
            $recipientId = ($user->id === $supervisorUserId) ? $employee->user_id : $supervisorUserId;
            $recipient = User::find($recipientId);

            if ($recipient) {
                try {
                    $recipient->notify(new SupervisorMessageNotification($message, $user));
                } catch (\Exception $notifyEx) {
                    Log::warning('SUPERVISOR_MSG: Notification failed', [
                        'recipient_id' => $recipientId,
                        'error' => $notifyEx->getMessage(),
                    ]);
                }
            }

            DB::commit();

            Log::info('SUPERVISOR_MSG: Message sent', [
                'message_id' => $message->id,
                'sender_id' => $user->id,
                'employee_id' => $employeeId,
                'supervisor_user_id' => $supervisorUserId,
            ]);

            return response()->json(['data' => $message], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SUPERVISOR_MSG: Failed to send message', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to send message'], 500);
        }
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json(['count' => 0]);
        }

        try {
            [$employeeId, $supervisorUserId] = $this->resolveThread($user, $employee);

            if (!$supervisorUserId) {
                return response()->json(['count' => 0]);
            }

            $count = SupervisorMessage::where('employee_id', $employeeId)
                ->where('supervisor_user_id', $supervisorUserId)
                ->where('sender_id', '!=', $user->id)
                ->whereNull('read_at')
                ->count();

            return response()->json(['count' => $count]);

        } catch (\Exception $e) {
            return response()->json(['count' => 0]);
        }
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        try {
            $message = SupervisorMessage::findOrFail($id);

            if ($message->sender_id === $user->id) {
                return response()->json(['message' => 'Cannot mark your own message as read'], 422);
            }

            if (!$this->userCanAccessMessage($user, $message)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $message->update(['read_at' => now()]);

            return response()->json(['message' => 'Message marked as read']);

        } catch (\Exception $e) {
            Log::error('SUPERVISOR_MSG: Failed to mark read', [
                'message_id' => $id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to mark message as read'], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        try {
            $message = SupervisorMessage::findOrFail($id);

            if ($message->sender_id !== $user->id) {
                return response()->json(['message' => 'You can only delete your own messages'], 403);
            }

            if ($message->created_at->diffInMinutes(now()) > 5) {
                return response()->json(['message' => 'Messages can only be deleted within 5 minutes of sending'], 422);
            }

            $message->delete();

            Log::info('SUPERVISOR_MSG: Message deleted', [
                'message_id' => $id,
                'user_id' => $user->id,
            ]);

            return response()->json(['message' => 'Message deleted']);

        } catch (\Exception $e) {
            Log::error('SUPERVISOR_MSG: Failed to delete message', [
                'message_id' => $id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to delete message'], 500);
        }
    }

    private function resolveThread(User $user, Employee $employee): array
    {
        // Caller is the employee side
        if ($user->role === 'employee') {
            $supervisorUserId = null;
            
            // Try to get manager from the employee relationship
            if (!$employee->relationLoaded('manager')) {
                $employee->load('manager');
            }
            
            if ($employee->manager) {
                $supervisorUserId = $employee->manager->id;
            } elseif ($employee->manager_id) {
                $supervisorUserId = $employee->manager_id;
            }
            
            return [$employee->id, $supervisorUserId];
        }

        // Caller is manager/admin - they are the supervisor side
        $subEmployeeId = request()->query('employee_id');

        if ($subEmployeeId) {
            $subEmployee = Employee::where('id', $subEmployeeId)
                ->where('manager_id', $user->id)
                ->first();

            if ($subEmployee) {
                return [$subEmployee->id, $user->id];
            }
        }

        // Default: first active subordinate
        $subEmployee = Employee::where('manager_id', $user->id)
            ->where('status', 'active')
            ->first();

        return $subEmployee ? [$subEmployee->id, $user->id] : [$employee->id, null];
    }

    private function userCanAccessMessage(User $user, SupervisorMessage $message): bool
    {
        $employeeRecord = Employee::find($message->employee_id);
        if ($employeeRecord && $employeeRecord->user_id === $user->id) return true;

        if ($message->supervisor_user_id === $user->id) return true;

        return false;
    }
}