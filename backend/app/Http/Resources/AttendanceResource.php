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
            'hoursWorked' => $this->total_hours ? (float) $this->total_hours : $this->calculateHours(),
            'breakMinutes' => $this->break_minutes ?? 0,
            'status' => $this->status ?? 'present',
            'notes' => $this->notes,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'overtimeHours' => $this->getOvertimeHours(),
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

    /**
     * Calculate hours if not already calculated
     */
    private function calculateHours(): float
    {
        if (!$this->clock_in || !$this->clock_out) {
            return 0;
        }

        try {
            $date = $this->date instanceof Carbon
                ? $this->date->format('Y-m-d')
                : Carbon::parse($this->date)->format('Y-m-d');
            $clockIn = Carbon::parse($date . ' ' . $this->clock_in);
            $clockOut = Carbon::parse($date . ' ' . $this->clock_out);

            $minutes = $clockOut->diffInMinutes($clockIn);
            $minutes -= ($this->break_minutes ?? 0);

            return max(0, round($minutes / 60, 2));
        } catch (\Exception $e) {
            return 0;
        }
    }
}