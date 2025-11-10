<?php
namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_in' => 'sometimes|date_format:H:i',
            'clock_out' => 'sometimes|date_format:H:i|after:clock_in',
            'break_minutes' => 'sometimes|integer|min:0|max:240',
            'notes' => 'nullable|string|max:500',
            'status' => 'sometimes|in:present,absent,late,half_day',
        ];
    }

    public function messages(): array
    {
        return [
            'clock_in.date_format' => 'Clock in time must be in HH:MM format',
            'clock_out.date_format' => 'Clock out time must be in HH:MM format',
            'clock_out.after' => 'Clock out time must be after clock in time',
            'break_minutes.integer' => 'Break minutes must be a whole number',
            'break_minutes.min' => 'Break minutes cannot be negative',
            'break_minutes.max' => 'Break minutes cannot exceed 4 hours',
        ];
    }
}