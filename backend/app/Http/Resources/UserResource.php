<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name . ' ' . $this->last_name,
            'email' => $this->email,
            'role' => $this->role,
            'is_superadmin' => (bool) $this->is_superadmin,
            'email_verified_at' => $this->email_verified_at?->format('Y-m-d H:i:s'),
            
            // Relationships
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'current_business' => new BusinessResource($this->whenLoaded('currentBusiness')),
            'businesses' => BusinessResource::collection($this->whenLoaded('businesses')),
            
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}