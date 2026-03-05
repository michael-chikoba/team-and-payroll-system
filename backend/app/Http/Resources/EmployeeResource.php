<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $request->user();
        $isOwnRecord = $user && $user->id === $this->user_id;
        $role = $user->role ?? 'guest';

        // Base data everyone can see
        $data = [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'full_name' => $this->user->full_name ?? null,
            'position' => $this->position,
            'department' => $this->department,
            'profile_pic' => $this->profile_pic,
        ];

        // Role-based additional data
        if ($isOwnRecord || $role === 'admin' || $role === 'superadmin') {
            $data['phone'] = $this->phone;
            $data['email'] = $this->email;
            $data['date_of_birth'] = $this->date_of_birth;
            $data['national_id'] = $this->national_id;
            $data['address'] = $this->address;
            $data['emergency_contact'] = $this->emergency_contact;
        }

        if ($role === 'admin' || $role === 'superadmin') {
            $data['base_salary'] = $this->base_salary;
            $data['transport_allowance'] = $this->transport_allowance;
            $data['lunch_allowance'] = $this->lunch_allowance;
            $data['bank_details'] = $this->bank_details;
        }

        return $data;
    }
}