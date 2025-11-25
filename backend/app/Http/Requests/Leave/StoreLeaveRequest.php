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
            'type' => 'required|string|in:annual,sick,maternity,paternity,bereavement,unpaid',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:10|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120', // 5MB max
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Please select a leave type.',
            'type.in' => 'Invalid leave type. Must be one of: annual, sick, maternity, paternity, bereavement, or unpaid.',
            'start_date.required' => 'Start date is required.',
            'start_date.after_or_equal' => 'Start date must be today or a future date.',
            'end_date.required' => 'End date is required.',
            'end_date.after_or_equal' => 'End date must be on or after the start date.',
            'reason.required' => 'Please provide a reason for your leave request.',
            'reason.min' => 'Reason must be at least 10 characters.',
            'reason.max' => 'Reason cannot exceed 1000 characters.',
            'attachment.mimes' => 'Attachment must be a PDF, DOC, DOCX, JPG, or PNG file.',
            'attachment.max' => 'Attachment size cannot exceed 5MB.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->start_date && $this->end_date) {
                $start = Carbon::parse($this->start_date);
                $end = Carbon::parse($this->end_date);
                
                // Check if leave duration exceeds 30 days
                if ($start->diffInDays($end) > 30) {
                    $validator->errors()->add('end_date', 'Leave duration cannot exceed 30 days.');
                }
                
                // Optional: Check if dates are on weekends (customize based on your business rules)
                // if ($start->isWeekend()) {
                //     $validator->errors()->add('start_date', 'Start date cannot be on a weekend.');
                // }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Ensure type is lowercase (defensive programming)
        if ($this->has('type')) {
            $this->merge([
                'type' => strtolower($this->type)
            ]);
        }
    }
}