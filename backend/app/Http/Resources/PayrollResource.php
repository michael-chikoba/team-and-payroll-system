<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payroll_period' => $this->payroll_period,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'status' => $this->status,
            'total_gross' => (float) $this->total_gross,
            'total_net' => (float) $this->total_net,
            'employee_count' => $this->employee_count,
            'processed_at' => $this->processed_at?->format('Y-m-d H:i:s'),
            'payslips' => PayslipResource::collection($this->whenLoaded('payslips')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}