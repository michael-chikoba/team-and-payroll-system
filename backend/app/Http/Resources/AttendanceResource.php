<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date instanceof Carbon
                ? $this->date->format('Y-m-d')
                : Carbon::parse($this->date)->format('Y-m-d'),
            'checkIn' => $this->formatTime($this->clock_in),
            'checkOut' => $this->formatTime($this->clock_out),
            
            // Use the calculated hours from the model
            'hoursWorked' => $this->total_hours ? (float) $this->total_hours : 0,
            'regularHours' => $this->regular_hours ? (float) $this->regular_hours : 0,
            'overtimeHours' => $this->overtime_hours ? (float) $this->overtime_hours : 0,
            
            'breakMinutes' => $this->break_minutes ?? 0,
            'status' => $this->status ?? 'present',
            'notes' => $this->notes,
            
            // Include shift and overtime session info
            'isOvertimeSession' => $this->is_overtime_session ?? false,
            'hasShift' => $this->shift_assignment_id !== null,
            'shift' => $this->when($this->shiftAssignment, function() {
                return [
                    'id' => $this->shiftAssignment->id,
                    'type' => $this->shiftAssignment->shift_type,
                    'startTime' => $this->shiftAssignment->start_time,
                    'endTime' => $this->shiftAssignment->end_time,
                    'status' => $this->shiftAssignment->status,
                ];
            }),
            
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            
            // Include parent attendance if this is an overtime session
            'parentAttendance' => $this->when($this->parent_attendance_id, function() {
                return $this->parentAttendance ? [
                    'id' => $this->parentAttendance->id,
                    'date' => $this->parentAttendance->date->format('Y-m-d'),
                    'regularHours' => (float) $this->parentAttendance->regular_hours,
                    'overtimeHours' => (float) $this->parentAttendance->overtime_hours,
                ] : null;
            }),
            
            'createdAt' => $this->created_at->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Format time to readable format (12-hour with AM/PM)
     */
    private function formatTime($time): ?string
    {
        if (!$time) {
            return null;
        }

        try {
            // Handle if time comes with full datetime
            if (strpos($time, ' ') !== false) {
                $carbon = Carbon::parse($time);
            } else {
                // Time only (HH:MM:SS)
                $carbon = Carbon::parse($time);
            }
            
            return $carbon->format('h:i A'); // 08:47 AM format
        } catch (\Exception $e) {
            return $time;
        }
    }
}