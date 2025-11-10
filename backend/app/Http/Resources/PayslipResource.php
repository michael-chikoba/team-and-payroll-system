<?php
// Updated PayslipResource to include all relevant fields
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayslipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee?->employee_id ?? 'N/A',
            'employee_name' => $this->employee?->user?->first_name . ' ' . $this->employee?->user?->last_name ?? 'N/A',
            'department' => $this->employee?->department ?? 'N/A',
            'pay_period_start' => $this->pay_period_start?->format('Y-m-d'),
            'pay_period_end' => $this->pay_period_end?->format('Y-m-d'),
            'payment_date' => $this->payment_date?->format('Y-m-d'),
            'basic_salary' => (float) $this->basic_salary,
            'house_allowance' => (float) ($this->house_allowance ?? 0),
            'transport_allowance' => (float) ($this->transport_allowance ?? 0),
            'other_allowances' => (float) ($this->other_allowances ?? 0),
            'overtime_hours' => (float) ($this->overtime_hours ?? 0),
            'overtime_rate' => (float) ($this->overtime_rate ?? 0),
            'overtime_pay' => (float) $this->overtime_pay,
            'gross_salary' => (float) $this->gross_salary,
            'napsa' => (float) ($this->napsa ?? 0),
            'paye' => (float) ($this->paye ?? 0),
            'nhima' => (float) ($this->nhima ?? 0),
            'other_deductions' => (float) ($this->other_deductions ?? 0),
            'total_deductions' => (float) $this->total_deductions,
            'net_pay' => (float) $this->net_pay,
            'breakdown' => $this->breakdown,
            'pdf_path' => $this->pdf_path,
            'is_sent' => $this->is_sent,
            'status' => $this->status ?? 'draft',
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'payroll' => new PayrollResource($this->whenLoaded('payroll')),
            'download_url' => $this->when($this->pdf_path, function () {
                return route('api.payslips.download', $this->id);
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}