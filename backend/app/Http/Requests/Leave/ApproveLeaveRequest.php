<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class ApproveLeaveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'status' => 'required|string|in:approved,rejected',
            'manager_notes' => 'nullable|string|max:500',
        ];
    }
}