<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'total_days' => $this->total_days,
            'reason' => $this->reason,
            'status' => $this->status,
            'manager_notes' => $this->manager_notes,
            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee->id,
                    'department' => $this->employee->department,
                    'full_name' => $this->employee->full_name,
                    'user' => $this->employee->user ? [
                        'id' => $this->employee->user->id,
                        'name' => $this->employee->user->name,
                        'first_name' => $this->employee->user->first_name,
                        'last_name' => $this->employee->user->last_name,
                        'email' => $this->employee->user->email,
                    ] : null,
                ];
            }),
            'manager' => $this->whenLoaded('manager', function () {
                return $this->manager ? [
                    'id' => $this->manager->id,
                    'name' => $this->manager->name,
                ] : null;
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}