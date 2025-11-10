<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePayslipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'payroll_id' => 'required|exists:payrolls,id',
            'send_email' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'payroll_id.required' => 'Payroll ID is required',
            'payroll_id.exists' => 'The selected payroll does not exist',
        ];
    }
}