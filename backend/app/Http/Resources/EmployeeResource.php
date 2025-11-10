<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
   public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name ?? $this->whenLoaded('user', fn() => $this->user?->first_name ?? 'N/A'),
            'last_name' => $this->last_name ?? $this->whenLoaded('user', fn() => $this->user?->last_name ?? 'N/A'),
            'email' => $this->whenLoaded('user', fn() => $this->user?->email ?? 'N/A'),
            'role' => $this->whenLoaded('user', fn() => $this->user?->role ?? 'N/A'),
            'position' => $this->position ?? 'N/A',
            'department' => $this->department ?? 'N/A',
            'base_salary' => $this->base_salary ?? 0,
            'employment_type' => $this->employment_type ?? 'N/A',
            'hire_date' => $this->hire_date ?? $this->created_at,
            'manager_id' => $this->manager_id ?? null,
            'manager' => $this->whenLoaded('manager', function () {
                if ($this->manager?->user) {
                    return trim(($this->manager->user->first_name ?? '') . ' ' . ($this->manager->user->last_name ?? ''));
                }
                return 'No Manager';
            }),
            'created_at' => $this->created_at,
        ];
    }
}