<?php

namespace App\Http\Controllers\Api;

use App\Models\ShiftAssignment;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ShiftAssignmentController extends Controller
{
    /**
     * Get shift assignments (filtered by role)
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = ShiftAssignment::with(['employee.user', 'assignedBy', 'business', 'department', 'shift']);

        // Apply filters based on user role
        if ($user->hasRole('super-admin')) {
            // Super admin sees all assignments
            if ($request->has('business_id')) {
                $query->where('business_id', $request->business_id);
            }
            if ($request->has('country_id')) {
                $query->where('country_id', $request->country_id);
            }
        } elseif ($user->hasRole('admin') || $user->hasRole('manager')) {
            // Admin/Manager sees their business assignments
            if ($user->employee && $user->employee->business_id) {
                $query->where('business_id', $user->employee->business_id);
            }
            
            // Manager sees only their department
            if ($user->hasRole('manager') && $user->employee && $user->employee->department_id) {
                $query->where('department_id', $user->employee->department_id);
            }
        } else {
            // Regular employee sees only their assignments
            if ($user->employee) {
                $query->where('employee_id', $user->employee->id);
            } else {
                return response()->json(['assignments' => []]);
            }
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('shift_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->where('shift_date', '<=', $request->to_date);
        }

        // Default to upcoming shifts
        if (!$request->has('from_date') && !$request->has('show_all')) {
            $query->where('shift_date', '>=', Carbon::today());
        }

        $assignments = $query->orderBy('shift_date', 'asc')
                            ->orderBy('start_time', 'asc')
                            ->get();

        return response()->json(['assignments' => $assignments]);
    }

    /**
     * Create shift assignment (Manager/Admin only)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'shift_date' => 'required|date|after_or_equal:today',
            'shift_type' => 'required|in:day,night,evening,morning',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'department_id' => 'nullable|exists:departments,id',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            $employee = Employee::findOrFail($request->employee_id);

            // Check for duplicate assignments
            $existing = ShiftAssignment::where('employee_id', $request->employee_id)
                ->where('shift_date', $request->shift_date)
                ->whereIn('status', ['pending', 'accepted'])
                ->exists();

            if ($existing) {
                return response()->json([
                    'message' => 'Employee already has a shift assigned for this date'
                ], 400);
            }

            $assignment = ShiftAssignment::create([
                'employee_id' => $request->employee_id,
                'assigned_by' => $user->id,
                'business_id' => $employee->business_id,
                'country_id' => $employee->country_id,
                'department_id' => $request->department_id ?? $employee->department_id,
                'shift_date' => $request->shift_date,
                'shift_type' => $request->shift_type,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'notes' => $request->notes,
                'status' => 'pending'
            ]);

            Log::info('Shift assigned', [
                'assignment_id' => $assignment->id,
                'employee_id' => $request->employee_id,
                'assigned_by' => $user->id
            ]);

            return response()->json([
                'message' => 'Shift assigned successfully',
                'assignment' => $assignment->load(['employee.user', 'assignedBy'])
            ], 201);

        } catch (\Exception $e) {
            Log::error('Shift assignment failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to assign shift',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk assign shifts
     */
    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'shift_date' => 'required|date|after_or_equal:today',
            'shift_type' => 'required|in:day,night,evening,morning',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'department_id' => 'nullable|exists:departments,id',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            $created = [];
            $skipped = [];

            foreach ($request->employee_ids as $employeeId) {
                // Check for duplicate
                $existing = ShiftAssignment::where('employee_id', $employeeId)
                    ->where('shift_date', $request->shift_date)
                    ->whereIn('status', ['pending', 'accepted'])
                    ->exists();

                if ($existing) {
                    $skipped[] = $employeeId;
                    continue;
                }

                $employee = Employee::find($employeeId);
                
                $assignment = ShiftAssignment::create([
                    'employee_id' => $employeeId,
                    'assigned_by' => $user->id,
                    'business_id' => $employee->business_id,
                    'country_id' => $employee->country_id,
                    'department_id' => $request->department_id ?? $employee->department_id,
                    'shift_date' => $request->shift_date,
                    'shift_type' => $request->shift_type,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'notes' => $request->notes,
                    'status' => 'pending'
                ]);

                $created[] = $assignment;
            }

            return response()->json([
                'message' => 'Bulk assignment completed',
                'created' => count($created),
                'skipped' => count($skipped),
                'assignments' => $created
            ], 201);

        } catch (\Exception $e) {
            Log::error('Bulk shift assignment failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to assign shifts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update shift assignment
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'shift_date' => 'sometimes|date|after_or_equal:today',
            'shift_type' => 'sometimes|in:day,night,evening,morning',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:pending,accepted,rejected,completed,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $assignment = ShiftAssignment::findOrFail($id);
            
            // Only allow updates to pending or accepted assignments
            if (in_array($assignment->status, ['completed', 'cancelled'])) {
                return response()->json([
                    'message' => 'Cannot update completed or cancelled assignments'
                ], 400);
            }

            $assignment->update($request->only([
                'shift_date', 'shift_type', 'start_time', 'end_time', 'notes', 'status'
            ]));

            return response()->json([
                'message' => 'Assignment updated successfully',
                'assignment' => $assignment->load(['employee.user', 'assignedBy'])
            ]);

        } catch (\Exception $e) {
            Log::error('Assignment update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to update assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Employee accepts shift assignment
     */
    public function accept($id)
    {
        try {
            $assignment = ShiftAssignment::findOrFail($id);
            $user = auth()->user();

            // Verify this is the employee's assignment
            if ($user->employee && $user->employee->id !== $assignment->employee_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($assignment->status !== 'pending') {
                return response()->json([
                    'message' => 'Assignment cannot be accepted'
                ], 400);
            }

            $assignment->accept();

            return response()->json([
                'message' => 'Shift accepted successfully',
                'assignment' => $assignment
            ]);

        } catch (\Exception $e) {
            Log::error('Accept assignment failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to accept assignment'
            ], 500);
        }
    }

    /**
     * Employee rejects shift assignment
     */
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please provide a reason for rejection',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $assignment = ShiftAssignment::findOrFail($id);
            $user = auth()->user();

            // Verify this is the employee's assignment
            if ($user->employee && $user->employee->id !== $assignment->employee_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($assignment->status !== 'pending') {
                return response()->json([
                    'message' => 'Assignment cannot be rejected'
                ], 400);
            }

            $assignment->reject($request->reason);

            return response()->json([
                'message' => 'Shift rejected',
                'assignment' => $assignment
            ]);

        } catch (\Exception $e) {
            Log::error('Reject assignment failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to reject assignment'
            ], 500);
        }
    }

    /**
     * Cancel assignment (Manager/Admin only)
     */
    public function cancel($id)
    {
        try {
            $assignment = ShiftAssignment::findOrFail($id);
            
            if ($assignment->status === 'completed') {
                return response()->json([
                    'message' => 'Cannot cancel completed assignment'
                ], 400);
            }

            $assignment->cancel();

            return response()->json([
                'message' => 'Assignment cancelled successfully',
                'assignment' => $assignment
            ]);

        } catch (\Exception $e) {
            Log::error('Cancel assignment failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to cancel assignment'
            ], 500);
        }
    }

    /**
     * Delete assignment
     */
    public function destroy($id)
    {
        try {
            $assignment = ShiftAssignment::findOrFail($id);
            
            // Don't allow deletion of completed shifts
            if ($assignment->status === 'completed') {
                return response()->json([
                    'message' => 'Cannot delete completed assignment'
                ], 400);
            }

            $assignment->delete();

            return response()->json([
                'message' => 'Assignment deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete assignment failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to delete assignment'
            ], 500);
        }
    }

    /**
     * Get employee's upcoming shifts
     */
    public function myShifts()
    {
        $user = auth()->user();
        
        if (!$user->employee) {
            return response()->json(['assignments' => []]);
        }

        $assignments = ShiftAssignment::where('employee_id', $user->employee->id)
            ->where('shift_date', '>=', Carbon::today())
            ->whereIn('status', ['pending', 'accepted'])
            ->with(['assignedBy', 'department'])
            ->orderBy('shift_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json(['assignments' => $assignments]);
    }

    /**
     * Get today's shift for employee
     */
    public function todayShift()
    {
        $user = auth()->user();
        
        if (!$user->employee) {
            return response()->json(['assignment' => null]);
        }

        $assignment = ShiftAssignment::where('employee_id', $user->employee->id)
            ->whereDate('shift_date', Carbon::today())
            ->whereIn('status', ['accepted', 'pending'])
            ->with(['assignedBy', 'department', 'shift'])
            ->first();

        return response()->json(['assignment' => $assignment]);
    }
}