<?php

namespace App\Services;

use App\Models\ShiftAssignment;
use App\Models\Employee;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ShiftAssignmentService
{
    protected LeaveBalanceService $leaveBalanceService;

    public function __construct(LeaveBalanceService $leaveBalanceService)
    {
        $this->leaveBalanceService = $leaveBalanceService;
    }

    /**
     * Validate shift assignment before creating
     */
    public function validateShiftAssignment(int $employeeId, string $date): array
    {
        $errors = [];

        // Check if employee exists
        $employee = Employee::find($employeeId);
        if (!$employee) {
            $errors[] = "Employee not found";
            return ['valid' => false, 'errors' => $errors];
        }

        // Check if employee is on leave
        if (!$this->leaveBalanceService->canAssignShift($employeeId, $date)) {
            $reason = $this->leaveBalanceService->getShiftBlockReason($employeeId, $date);
            $errors[] = $reason ?? "Employee is on approved leave on this date";
        }

        // Check for duplicate shift on same date
        $existingShift = ShiftAssignment::where('employee_id', $employeeId)
            ->where('shift_date', $date)
            ->whereIn('status', ['pending', 'accepted'])
            ->first();

        if ($existingShift) {
            $errors[] = "Employee already has a shift assigned for this date";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'employee' => $employee
        ];
    }

    /**
     * Assign shift to employee
     */
    public function assignShift(array $data): array
    {
        Log::info('Attempting to assign shift', [
            'employee_id' => $data['employee_id'],
            'date' => $data['shift_date']
        ]);

        // Validate assignment
        $validation = $this->validateShiftAssignment(
            $data['employee_id'],
            $data['shift_date']
        );

        if (!$validation['valid']) {
            Log::warning('Shift assignment validation failed', [
                'employee_id' => $data['employee_id'],
                'errors' => $validation['errors']
            ]);
            
            return [
                'success' => false,
                'errors' => $validation['errors']
            ];
        }

        DB::beginTransaction();
        try {
            $employee = $validation['employee'];

            // Create shift assignment
            $shift = ShiftAssignment::create([
                'employee_id' => $data['employee_id'],
                'assigned_by' => $data['assigned_by'],
                'business_id' => $data['business_id'] ?? $employee->business_id,
                'country_id' => $data['country_id'] ?? $employee->country_id,
                'department_id' => $data['department_id'] ?? $employee->department_id,
                'shift_date' => $data['shift_date'],
                'shift_type' => $data['shift_type'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
            ]);

            DB::commit();

            Log::info('Shift assigned successfully', [
                'shift_id' => $shift->id,
                'employee_id' => $employee->id,
                'date' => $shift->shift_date
            ]);

            return [
                'success' => true,
                'shift' => $shift,
                'message' => 'Shift assigned successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to assign shift', [
                'employee_id' => $data['employee_id'],
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'errors' => ['Failed to assign shift: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Bulk assign shifts to multiple employees
     */
    public function bulkAssignShifts(array $employeeIds, array $shiftData, int $assignedBy): array
    {
        Log::info('Bulk assigning shifts', [
            'employee_count' => count($employeeIds),
            'date' => $shiftData['shift_date']
        ]);

        $results = [
            'success' => [],
            'failed' => [],
            'total' => count($employeeIds)
        ];

        foreach ($employeeIds as $employeeId) {
            $data = array_merge($shiftData, [
                'employee_id' => $employeeId,
                'assigned_by' => $assignedBy
            ]);

            $result = $this->assignShift($data);

            if ($result['success']) {
                $results['success'][] = [
                    'employee_id' => $employeeId,
                    'shift' => $result['shift']
                ];
            } else {
                $results['failed'][] = [
                    'employee_id' => $employeeId,
                    'errors' => $result['errors']
                ];
            }
        }

        Log::info('Bulk shift assignment completed', [
            'total' => $results['total'],
            'success' => count($results['success']),
            'failed' => count($results['failed'])
        ]);

        return $results;
    }

    /**
     * Get available employees for shift assignment (not on leave)
     */
    public function getAvailableEmployees(string $date, ?int $businessId = null, ?int $departmentId = null): array
    {
        $query = Employee::with(['user'])
            ->whereHas('user', function($q) {
                $q->where('status', 'active');
            });

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $employees = $query->get();

        $available = [];
        $unavailable = [];

        foreach ($employees as $employee) {
            if ($this->leaveBalanceService->canAssignShift($employee->id, $date)) {
                $available[] = [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                    'employee_id' => $employee->employee_id,
                    'department' => $employee->department,
                    'position' => $employee->position,
                ];
            } else {
                $reason = $this->leaveBalanceService->getShiftBlockReason($employee->id, $date);
                $unavailable[] = [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                    'reason' => $reason
                ];
            }
        }

        return [
            'available' => $available,
            'unavailable' => $unavailable,
            'total' => $employees->count(),
            'available_count' => count($available)
        ];
    }

    /**
     * Accept shift assignment
     */
    public function acceptShift(int $shiftId, int $employeeId): array
    {
        try {
            $shift = ShiftAssignment::where('id', $shiftId)
                ->where('employee_id', $employeeId)
                ->where('status', 'pending')
                ->firstOrFail();

            // Double-check leave status
            if (!$this->leaveBalanceService->canAssignShift($employeeId, $shift->shift_date)) {
                return [
                    'success' => false,
                    'message' => 'Cannot accept shift - you are on approved leave for this date'
                ];
            }

            $shift->update([
                'status' => 'accepted',
                'accepted_at' => now()
            ]);

            Log::info('Shift accepted', [
                'shift_id' => $shift->id,
                'employee_id' => $employeeId
            ]);

            return [
                'success' => true,
                'shift' => $shift,
                'message' => 'Shift accepted successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to accept shift', [
                'shift_id' => $shiftId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to accept shift: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reject shift assignment
     */
    public function rejectShift(int $shiftId, int $employeeId, ?string $reason = null): array
    {
        try {
            $shift = ShiftAssignment::where('id', $shiftId)
                ->where('employee_id', $employeeId)
                ->where('status', 'pending')
                ->firstOrFail();

            $shift->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejection_reason' => $reason
            ]);

            Log::info('Shift rejected', [
                'shift_id' => $shift->id,
                'employee_id' => $employeeId,
                'reason' => $reason
            ]);

            return [
                'success' => true,
                'shift' => $shift,
                'message' => 'Shift rejected'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to reject shift', [
                'shift_id' => $shiftId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to reject shift: ' . $e->getMessage()
            ];
        }
    }
}