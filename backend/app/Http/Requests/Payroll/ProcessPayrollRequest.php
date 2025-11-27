<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPayrollRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust authorization logic as needed (e.g., check if user has admin role)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payroll_period' => [
                'required',
                'string',
                'regex:/^\d{4}-\d{2}$/',
                'date_format:Y-m',
            ],
            'start_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
            ],
            'end_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],
            'employee_ids' => [
                'sometimes',
                'array',
                'min:0',
            ],
            'employee_ids.*' => [
                'integer',
                'exists:employees,id',
            ],
                    'adjustments' => 'sometimes|array',
        'adjustments.*.overtime_bonus' => 'sometimes|numeric|min:0',
        'adjustments.*.other_bonuses' => 'sometimes|numeric|min:0',
        'adjustments.*.loan_deductions' => 'sometimes|numeric|min:0',
        'adjustments.*.advance_deductions' => 'sometimes|numeric|min:0',
        ];
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'payroll_period.regex' => 'The payroll period must be in YYYY-MM format.',
            'payroll_period.date_format' => 'The payroll period must be a valid month in YYYY-MM format.',
            'start_date.date_format' => 'The start date must be in YYYY-MM-DD format.',
            'end_date.date_format' => 'The end date must be in YYYY-MM-DD format.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
            'employee_ids.*.exists' => 'One or more selected employees do not exist.',
        ];
    }
}