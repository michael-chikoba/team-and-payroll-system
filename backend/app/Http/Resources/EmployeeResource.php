<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'manager_id' => $this->manager_id,
            'employee_id' => $this->employee_id,
            'position' => $this->position,
            'department' => $this->department,
            'base_salary' => $this->base_salary,
            'transport_allowance' => $this->transport_allowance,
            'lunch_allowance' => $this->lunch_allowance,
            'hire_date' => $this->hire_date,
            'employment_type' => $this->employment_type,
            'bank_details' => $this->bank_details,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth,
            'national_id' => $this->national_id,
            'address' => $this->address,
            'emergency_contact' => $this->emergency_contact,
            'profile_pic' => $this->profile_pic,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'user' => $this->whenLoaded('user'),
            'manager' => $this->whenLoaded('manager'),
            'attendances' => $this->whenLoaded('attendances'),
            'leaves' => $this->whenLoaded('leaves'),
            'leave_balances' => $this->whenLoaded('leaveBalances'),
            'payslips' => $this->whenLoaded('payslips'),
            'documents' => $this->whenLoaded('documents'),
            'advances' => $this->whenLoaded('advances'),
            'loans' => $this->whenLoaded('loans'),
        ];
    }
}