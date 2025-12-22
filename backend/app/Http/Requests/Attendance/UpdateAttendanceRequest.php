<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Allow HH:MM or HH:MM:SS
            'clock_in'      => 'sometimes|date_format:H:i,H:i:s', 
            'clock_out'     => 'sometimes|date_format:H:i,H:i:s|after:clock_in',
            'break_minutes' => 'sometimes|integer|min:0|max:240',
            'notes'         => 'nullable|string|max:500',
            // Added 'completed' and 'on_leave' to support all system statuses
            'status'        => 'sometimes|in:present,absent,late,half_day,completed,on_leave',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'clock_in.date_format'  => 'Clock in time must be in HH:MM format',
            'clock_out.date_format' => 'Clock out time must be in HH:MM format',
            'clock_out.after'       => 'Clock out time must be after clock in time',
            'break_minutes.integer' => 'Break minutes must be a whole number',
            'break_minutes.max'     => 'Break minutes cannot exceed 4 hours',
            'status.in'             => 'The selected status is invalid.',
        ];
    }
}