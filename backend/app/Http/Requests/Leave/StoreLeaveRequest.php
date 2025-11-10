<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

   public function rules(): array
    {
        return [
            'date' => 'required|date',
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i|after:clock_in',
            'break_minutes' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->start_date && $this->end_date) {
                $start = Carbon::parse($this->start_date);
                $end = Carbon::parse($this->end_date);
                
                if ($start->diffInDays($end) > 30) {
                    $validator->errors()->add('end_date', 'Leave cannot exceed 30 days.');
                }
            }
        });
    }
}