<?php

namespace App\Http\Controllers\Api;

use App\Models\Schedule;
use App\Models\ScheduleNotification;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

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
        // LOG INCOMING REQUEST
        Log::info('Schedule Store Request:', [
            'all_data' => $request->all(),
            'user_id' => auth()->id()
        ]);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:banner_creation,weekly_overview,test_sequence,live_games,multibets,news_section,other',
            'priority' => 'required|in:low,moderate,high,urgent',
            'scheduled_date' => 'nullable|date',
            'due_date' => 'required|date',
            'assigned_to' => 'required|integer|exists:employees,id',
            'meta_data' => 'nullable|array',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:pending,in_progress,completed,overdue'
        ]);

        if ($validator->fails()) {
            Log::error('Schedule Validation Failed:', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'received_data' => $request->all()
            ], 422);
        }

        try {
            // Get the employee to find their user_id
            $employee = Employee::find($request->assigned_to);
            
            if (!$employee) {
                return response()->json([
                    'message' => 'Employee not found',
                    'employee_id' => $request->assigned_to
                ], 404);
            }

            Log::info('Employee details:', [
                'employee_id' => $employee->id,
                'user_id' => $employee->user_id,
                'name' => $employee->full_name
            ]);

            $scheduleData = [
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'priority' => $request->priority,
                'start_date' => $request->scheduled_date ?? now(),  // Map scheduled_date to start_date
                'due_date' => $request->due_date,
                'assigned_to' => $employee->id,
                'assigned_user_id' => $employee->user_id,
                'metadata' => $request->meta_data ?? [],  // Map meta_data to metadata
                'notes' => $request->notes,
                'status' => $request->status ?? 'pending',
                'created_by' => auth()->id() ?? 1,
            ];

            Log::info('Creating schedule with data:', $scheduleData);

            $schedule = Schedule::create($scheduleData);

            // Create assignment notification
            if ($schedule->assigned_to) {
                try {
                    $schedule->notifications()->create([
                        'notification_type' => 'assigned',
                        'message' => "You have been assigned: {$schedule->title}",
                        'is_read' => false,
                        'employee_id' => $employee->id,
                        'user_id' => $employee->user_id
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to create notification:', ['error' => $e->getMessage()]);
                }
            }

            Log::info('Schedule created successfully:', [
                'schedule_id' => $schedule->id,
                'assigned_to_employee' => $employee->id,
                'assigned_to_user' => $employee->user_id
            ]);

            return response()->json([
                'message' => 'Schedule created successfully',
                'schedule' => $schedule->load('assignedEmployee', 'createdBy', 'notifications')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Schedule Creation Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to create schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $schedule = Schedule::with(['assignedEmployee', 'createdBy', 'notifications'])->findOrFail($id);
        return response()->json($schedule);
    }

    public function update(Request $request, $id)
    {
        Log::info('Schedule Update Request:', [
            'id' => $id,
            'data' => $request->all()
        ]);

        $schedule = Schedule::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:banner_creation,weekly_overview,test_sequence,live_games,multibets,news_section,other',
            'status' => 'sometimes|required|in:pending,in_progress,completed,overdue',
            'priority' => 'sometimes|required|in:low,moderate,high,urgent',
            'scheduled_date' => 'nullable|date',
            'due_date' => 'sometimes|required|date',
            'assigned_to' => 'nullable|integer|exists:employees,id',
            'meta_data' => 'nullable|array',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            Log::error('Schedule Update Validation Failed:', [
                'errors' => $validator->errors()->toArray()
            ]);
            
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = [];
            
            if ($request->has('title')) $updateData['title'] = $request->title;
            if ($request->has('description')) $updateData['description'] = $request->description;
            if ($request->has('type')) $updateData['type'] = $request->type;
            if ($request->has('priority')) $updateData['priority'] = $request->priority;
            if ($request->has('scheduled_date')) $updateData['start_date'] = $request->scheduled_date;  // Map to start_date
            if ($request->has('due_date')) $updateData['due_date'] = $request->due_date;
            if ($request->has('meta_data')) $updateData['metadata'] = $request->meta_data;  // Map to metadata
            if ($request->has('notes')) $updateData['notes'] = $request->notes;
            if ($request->has('status')) $updateData['status'] = $request->status;
            
            // Handle assigned_to change
            if ($request->has('assigned_to')) {
                $employee = Employee::find($request->assigned_to);
                if ($employee) {
                    $updateData['assigned_to'] = $employee->id;
                    $updateData['assigned_user_id'] = $employee->user_id;
                }
            }

            $schedule->update($updateData);

            // If marked as completed
            if ($request->has('status') && $request->status === 'completed') {
                $schedule->update(['completed_at' => now()]);
                
                try {
                    $schedule->notifications()->create([
                        'notification_type' => 'completed',
                        'message' => "Schedule completed: {$schedule->title}",
                        'is_read' => false
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to create completion notification:', ['error' => $e->getMessage()]);
                }
            }

            Log::info('Schedule updated successfully:', ['schedule_id' => $schedule->id]);

            return response()->json([
                'message' => 'Schedule updated successfully',
                'schedule' => $schedule->load('assignedEmployee', 'createdBy', 'notifications')
            ]);

        } catch (\Exception $e) {
            Log::error('Schedule Update Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to update schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $schedule = Schedule::findOrFail($id);
            $schedule->delete();

            Log::info('Schedule deleted successfully:', ['schedule_id' => $id]);

            return response()->json(['message' => 'Schedule deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Schedule Delete Error:', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to delete schedule',
                'error' => $e->getMessage()
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

    public function updateMeta(Request $request, $id)
    {
        try {
            $schedule = Schedule::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'meta_data' => 'required|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $schedule->update([
                'metadata' => $request->meta_data  // Map to metadata
            ]);

            Log::info('Schedule meta updated:', ['schedule_id' => $id, 'meta_data' => $request->meta_data]);

            return response()->json([
                'message' => 'Meta data updated successfully',
                'schedule' => $schedule
            ]);

        } catch (\Exception $e) {
            Log::error('Schedule Meta Update Error:', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to update meta data',
                'error' => $e->getMessage()
            ], 500);
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

    public function getNotifications()
    {
        try {
            $notifications = ScheduleNotification::with('schedule')
                ->orderBy('created_at', 'desc')
                ->take(50)
                ->get();

            return response()->json(['notifications' => $notifications]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch notifications:', ['error' => $e->getMessage()]);
            return response()->json(['notifications' => []]);
        }
    }

    public function markNotificationRead($id)
    {
        try {
            $notification = ScheduleNotification::findOrFail($id);
            $notification->update(['is_read' => true]);

            return response()->json([
                'message' => 'Notification marked as read',
                'notification' => $notification
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to mark notification as read'
            ], 500);
        }
    }

    public function markAllNotificationsRead()
    {
        try {
            ScheduleNotification::where('is_read', false)->update(['is_read' => true]);

            return response()->json(['message' => 'All notifications marked as read']);
        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read:', ['error' => $e->getMessage()]);
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
}