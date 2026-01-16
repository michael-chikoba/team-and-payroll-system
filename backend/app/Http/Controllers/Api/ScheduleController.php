<?php

namespace App\Http\Controllers\Api;

use App\Models\Schedule;
use App\Events\ScheduleAssigned;
use App\Events\ScheduleUpdated;
use App\Models\ScheduleNotification;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = Schedule::with(['assignedEmployee', 'createdBy', 'notifications']);

        // Filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Update overdue statuses
        Schedule::where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->update(['status' => 'overdue']);

        $schedules = $query->orderBy('due_date', 'asc')->get();

        return response()->json(['schedules' => $schedules]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|string',
                'priority' => 'required|string|in:low,moderate,high,urgent',
                'scheduled_date' => 'nullable|date',
                'due_date' => 'required|date',
                'assigned_to' => 'required|exists:employees,id',
                'notes' => 'nullable|string',
                'meta_data' => 'nullable|array',
                'status' => 'nullable|string|in:pending,in_progress,completed,overdue,cancelled',
            ]);

            // Map frontend fields to database fields
            $scheduleData = [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'],
                'priority' => $validated['priority'],
                'start_date' => $validated['scheduled_date'] ?? null,
                'due_date' => $validated['due_date'],
                'assigned_to' => $validated['assigned_to'],
                'notes' => $validated['notes'] ?? null,
                'metadata' => $validated['meta_data'] ?? null,
                'status' => $validated['status'] ?? 'pending',
                'created_by' => Auth::id(),
            ];

            $schedule = Schedule::create($scheduleData);

            Log::info('Schedule created', [
                'schedule_id' => $schedule->id,
                'assigned_to' => $schedule->assigned_to,
                'created_by' => $schedule->created_by
            ]);

            // Dispatch event to trigger notification
            event(new ScheduleAssigned($schedule));

            return response()->json([
                'message' => 'Schedule created successfully',
                'schedule' => $schedule->load(['assignedEmployee', 'createdBy'])
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed for schedule creation', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);
            
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Failed to create schedule', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to create schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $schedule = Schedule::findOrFail($id);
            
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'type' => 'sometimes|string',
                'priority' => 'sometimes|string|in:low,moderate,high,urgent',
                'scheduled_date' => 'sometimes|nullable|date',
                'due_date' => 'sometimes|date',
                'assigned_to' => 'sometimes|exists:employees,id',
                'notes' => 'nullable|string',
                'meta_data' => 'nullable|array',
                'status' => 'sometimes|in:pending,in_progress,completed,overdue,cancelled',
            ]);

            // Map frontend fields to database fields
            $updateData = [];
            
            if (isset($validated['title'])) $updateData['title'] = $validated['title'];
            if (isset($validated['description'])) $updateData['description'] = $validated['description'];
            if (isset($validated['type'])) $updateData['type'] = $validated['type'];
            if (isset($validated['priority'])) $updateData['priority'] = $validated['priority'];
            if (isset($validated['scheduled_date'])) $updateData['start_date'] = $validated['scheduled_date'];
            if (isset($validated['due_date'])) $updateData['due_date'] = $validated['due_date'];
            if (isset($validated['assigned_to'])) $updateData['assigned_to'] = $validated['assigned_to'];
            if (isset($validated['notes'])) $updateData['notes'] = $validated['notes'];
            if (isset($validated['meta_data'])) $updateData['metadata'] = $validated['meta_data'];
            if (isset($validated['status'])) $updateData['status'] = $validated['status'];

            $schedule->update($updateData);

            Log::info('Schedule updated', [
                'schedule_id' => $schedule->id,
                'updated_fields' => array_keys($updateData)
            ]);

            // Dispatch event to trigger notification
            event(new ScheduleUpdated($schedule));

            return response()->json([
                'message' => 'Schedule updated successfully',
                'schedule' => $schedule->fresh(['assignedEmployee', 'createdBy'])
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed for schedule update', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);
            
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Failed to update schedule', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to update schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUnreadCount()
    {
        try {
            $userId = auth()->id();
            $employee = Employee::where('user_id', $userId)->first();
            
            if (!$employee) {
                return response()->json(['count' => 0]);
            }
            
            $count = ScheduleNotification::where(function($query) use ($userId, $employee) {
                    $query->where('user_id', $userId)
                          ->orWhere('employee_id', $employee->id);
                })
                ->where('is_read', false)
                ->count();

            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch unread count:', ['error' => $e->getMessage()]);
            return response()->json(['count' => 0]);
        }
    }

    public function markNotificationRead($id)
    {
        try {
            $userId = auth()->id();
            $employee = Employee::where('user_id', $userId)->first();
            
            $notification = ScheduleNotification::where('id', $id)
                ->where(function($query) use ($userId, $employee) {
                    $query->where('user_id', $userId);
                    if ($employee) {
                        $query->orWhere('employee_id', $employee->id);
                    }
                })
                ->firstOrFail();
                
            $notification->update([
                'is_read' => true,
                'read_at' => now()
            ]);

            Log::info('Notification marked as read:', [
                'notification_id' => $id,
                'user_id' => $userId
            ]);

            return response()->json([
                'message' => 'Notification marked as read',
                'notification' => $notification
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read:', [
                'error' => $e->getMessage(),
                'notification_id' => $id
            ]);
            return response()->json([
                'message' => 'Failed to mark notification as read'
            ], 500);
        }
    }

    public function markAllNotificationsRead()
    {
        try {
            $userId = auth()->id();
            $employee = Employee::where('user_id', $userId)->first();
            
            $updated = ScheduleNotification::where('is_read', false)
                ->where(function($query) use ($userId, $employee) {
                    $query->where('user_id', $userId);
                    if ($employee) {
                        $query->orWhere('employee_id', $employee->id);
                    }
                })
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);

            Log::info('All notifications marked as read:', [
                'user_id' => $userId,
                'count' => $updated
            ]);

            return response()->json([
                'message' => 'All notifications marked as read',
                'count' => $updated
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read:', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Failed to mark all notifications as read'
            ], 500);
        }
    }

    public function getCalendarData(Request $request)
    {
        try {
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);

            $schedules = Schedule::whereYear('due_date', $year)
                ->whereMonth('due_date', $month)
                ->get()
                ->groupBy(function($schedule) {
                    return $schedule->due_date->format('Y-m-d');
                });

            return response()->json(['schedules' => $schedules]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch calendar data:', ['error' => $e->getMessage()]);
            return response()->json(['schedules' => []]);
        }
    }
    
    public function getEmployeeSchedules(Request $request)
    {
        try {
            $userId = auth()->id();
            
            Log::info('Fetching schedules for user:', ['user_id' => $userId]);
            
            $employee = Employee::where('user_id', $userId)->first();
            
            if (!$employee) {
                Log::warning('No employee record found for user:', ['user_id' => $userId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Employee record not found',
                    'schedules' => []
                ]);
            }
            
            Log::info('Employee found:', [
                'employee_id' => $employee->id,
                'user_id' => $userId,
                'name' => $employee->full_name
            ]);
            
            $schedules = Schedule::where(function($query) use ($employee, $userId) {
                    $query->where('assigned_to', $employee->id)
                          ->orWhere('assigned_user_id', $userId);
                })
                ->with(['assignedEmployee', 'createdBy'])
                ->orderBy('due_date', 'asc')
                ->get();
            
            Log::info('Found schedules:', [
                'count' => $schedules->count(),
                'employee_id' => $employee->id,
                'user_id' => $userId
            ]);
            
            if ($schedules->count() === 0) {
                $allSchedules = Schedule::select('id', 'title', 'assigned_to', 'assigned_user_id')->get();
                Log::info('All schedules in database:', [
                    'schedules' => $allSchedules->toArray()
                ]);
            }
            
            return response()->json([
                'success' => true,
                'schedules' => $schedules,
                'employee' => [
                    'id' => $employee->id,
                    'user_id' => $userId,
                    'name' => $employee->full_name
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch employee schedules:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch schedules',
                'error' => $e->getMessage(),
                'schedules' => []
            ], 500);
        }
    }
     public function complete($id)
    {
        try {
            $schedule = Schedule::findOrFail($id);
            
            $schedule->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            try {
                $schedule->notifications()->create([
                    'notification_type' => 'completed',
                    'message' => "Schedule completed: {$schedule->title}",
                    'is_read' => false
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to create completion notification:', ['error' => $e->getMessage()]);
            }

            Log::info('Schedule marked as complete:', ['schedule_id' => $id]);

            return response()->json([
                'message' => 'Schedule marked as complete',
                'schedule' => $schedule->load('assignedEmployee', 'createdBy', 'notifications')
            ]);

        } catch (\Exception $e) {
            Log::error('Schedule Complete Error:', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to complete schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}