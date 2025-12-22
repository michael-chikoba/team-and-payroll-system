<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\ProcessPayrollRequest;
use App\Http\Resources\PayrollResource;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(private PayrollCalculationService $payrollService) {}

    public function processPayroll(ProcessPayrollRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $period = $validated['payroll_period'];
        $adjustments = $validated['adjustments'] ?? [];

        $payroll = Payroll::firstOrCreate(
            ['payroll_period' => $period],
            [
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => 'draft',
            ]
        );

        try {
            $employeeIds = $validated['employee_ids'] ?? [];
            if (empty($employeeIds)) {
                $employeeIds = Employee::pluck('id')->toArray();
            }

            $processedCount = 0;
            foreach ($employeeIds as $empId) {
                $employee = Employee::find($empId);
                if (!$employee) continue;

                $employeeAdjustments = $adjustments[$empId] ?? [];
                $payslip = Payslip::where('payroll_id', $payroll->id)->where('employee_id', $empId)->first();

                if ($payslip) {
                    if (!empty($employeeAdjustments)) {
                        $this->applyAdjustmentsToPayslip($payslip, $employeeAdjustments);
                    }
                    $payslip->status = 'paid';
                    $payslip->save();
                } else {
                    $this->payrollService->createPayslipWithAdjustments($employee, $payroll, 'paid', $employeeAdjustments);
                }
                $processedCount++;
            }

            $this->payrollService->updatePayrollTotals($payroll);
            $payroll->status = 'completed';
            $payroll->processed_at = now();
            $payroll->save();

            return response()->json([
                'payroll' => new PayrollResource($payroll->fresh()),
                'message' => "Successfully processed payroll for {$processedCount} employee(s)",
                'processed_count' => $processedCount,
            ]);
        } catch (\Exception $e) {
            \Log::error('Payroll processing failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to process payroll: ' . $e->getMessage()], 500);
        }
    }

    private function applyAdjustmentsToPayslip(Payslip $payslip, array $adjustments): void
    {
        $bonuses = ($adjustments['overtime_bonus'] ?? 0) + ($adjustments['other_bonuses'] ?? 0);
        $deductions = ($adjustments['loan_deductions'] ?? 0) + ($adjustments['advance_deductions'] ?? 0);
        
        $payslip->gross_salary += $bonuses;
        $payslip->bonuses += $bonuses;
        $payslip->other_deductions += $deductions;
        
        // Recalculate total deductions based on stored dynamic breakdown if available
        // If the breakdown exists, we sum the statutory deduction amounts from it
        $statutoryTotal = 0;
        if (isset($payslip->breakdown['deductions_breakdown']['statutory_total'])) {
            $statutoryTotal = $payslip->breakdown['deductions_breakdown']['statutory_total'];
        } else {
            // Fallback for old records
            $statutoryTotal = ($payslip->napsa ?? 0) + ($payslip->nhima ?? 0) + ($payslip->pension ?? 0);
        }

        $payslip->total_deductions = $payslip->paye + $statutoryTotal + $payslip->other_deductions;
        $payslip->net_pay = $payslip->gross_salary - $payslip->total_deductions;
        
        $breakdown = $payslip->breakdown ?? [];
        $breakdown['adjustments'] = $adjustments;
        $payslip->breakdown = $breakdown;
    }

    // ... updateStatus, employeesSummary (similar logic to previous) ...

    public function employeesSummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payroll_period' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'per_page' => 'integer|min:1|max:1000',
            'business_id' => 'nullable|integer',
        ]);

        $period = $validated['payroll_period'];
        $businessId = $validated['business_id'] ?? null;

        $payroll = Payroll::firstOrCreate(['payroll_period' => $period], [
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'draft',
        ]);

        $employeeQuery = Employee::with('user');
        if ($businessId) $employeeQuery->where('business_id', $businessId);
        $employees = $employeeQuery->get();

        $summaryData = [];
        foreach ($employees as $employee) {
            $payslip = Payslip::where('payroll_id', $payroll->id)->where('employee_id', $employee->id)->first();

            if ($payslip) {
                $payslipData = $this->formatDetailedPayslip($payslip);
                $status = $payslip->status;
            } else {
                $previewData = $this->payrollService->generatePayslipPreview($employee, $payroll);
                $payslipData = $this->formatPreviewPayslip($previewData, $employee, $payroll);
                $status = 'pending';
            }

            $summaryData[] = [
                'id' => $employee->id,
                'name' => $employee->user ? $employee->user->first_name . ' ' . $employee->user->last_name : 'Unknown',
                'position' => $employee->position ?? 'Unassigned',
                'business_name' => $employee->business?->name ?? 'No Business',
                'base_salary' => (float) $employee->base_salary,
                'gross_salary' => (float) ($payslipData['summary']['gross_pay'] ?? 0),
                'net_pay' => (float) ($payslipData['summary']['net_pay'] ?? 0),
                'payroll_status' => $status,
                'pay_period' => $period,
                'payslip_data' => $payslipData,
            ];
        }

        return response()->json([
            'data' => $summaryData,
            'payroll_id' => $payroll->id,
        ]);
    }
      public function updateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:employees,id',
            'status' => 'required|in:pending,paid',
            'payroll_period' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'adjustments' => 'sometimes|array',
            'adjustments.*.overtime_bonus' => 'sometimes|numeric|min:0',
            'adjustments.*.other_bonuses' => 'sometimes|numeric|min:0',
            'adjustments.*.loan_deductions' => 'sometimes|numeric|min:0',
            'adjustments.*.advance_deductions' => 'sometimes|numeric|min:0',
        ]);

        $period = $validated['payroll_period'];
        $adjustments = $validated['adjustments'] ?? [];
        
        // Find or create payroll for the period
        $payroll = Payroll::firstOrCreate(
            ['payroll_period' => $period],
            [
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => 'draft',
            ]
        );

        $updatedCount = 0;
        $createdCount = 0;

        foreach ($validated['employee_ids'] as $empId) {
            $employee = Employee::find($empId);
            
            if (!$employee) {
                continue;
            }

            // Get adjustments for this employee
            $employeeAdjustments = $adjustments[$empId] ?? [];

            // Find existing payslip
            $payslip = Payslip::where('payroll_id', $payroll->id)
                ->where('employee_id', $empId)
                ->first();

            if ($payslip) {
                // Update existing payslip status and apply adjustments
                if (!empty($employeeAdjustments)) {
                    $this->applyAdjustmentsToPayslip($payslip, $employeeAdjustments);
                }
                $payslip->status = $validated['status'];
                $payslip->save();
                $updatedCount++;
            } else {
                // Create new payslip with calculated values and adjustments
                if (!empty($employeeAdjustments)) {
                    $this->payrollService->createPayslipWithAdjustments($employee, $payroll, $validated['status'], $employeeAdjustments);
                } else {
                    $this->payrollService->createPayslip($employee, $payroll, $validated['status']);
                }
                $createdCount++;
            }
        }

        // Update payroll totals
        if ($updatedCount > 0 || $createdCount > 0) {
            $this->payrollService->updatePayrollTotals($payroll);
            
            // Mark payroll as completed if it was draft
            if ($payroll->status === 'draft') {
                $payroll->status = 'completed';
                $payroll->processed_at = now();
                $payroll->save();
            }
        }

        $message = sprintf(
            'Successfully processed: %d updated, %d created.',
            $updatedCount,
            $createdCount
        );

        return response()->json([
            'message' => $message,
            'updated_count' => $updatedCount,
            'created_count' => $createdCount,
            'payroll_id' => $payroll->id,
        ]);
    }

    

    public function viewEmployeePayslip(Request $request, int $employeeId): JsonResponse
    {
        $payrollPeriod = $request->query('payroll_period');
        $employee = Employee::with('user')->findOrFail($employeeId);
        
        $payroll = Payroll::where('payroll_period', $payrollPeriod)->first();
        if (!$payroll) {
            return response()->json(['message' => 'No payroll found', 'data' => null], 404);
        }

        $payslip = Payslip::where('payroll_id', $payroll->id)->where('employee_id', $employeeId)->first();

        if (!$payslip) {
            $payslipData = $this->payrollService->generatePayslipPreview($employee, $payroll);
            return response()->json([
                'data' => $this->formatPreviewPayslip($payslipData, $employee, $payroll),
                'is_preview' => true
            ]);
        }

        return response()->json([
            'data' => $this->formatDetailedPayslip($payslip),
            'is_preview' => false
        ]);
    }

    /**
     * Format PREVIEW data (Generic Statutory Array)
     */
    private function formatPreviewPayslip(array $previewData, Employee $employee, Payroll $payroll): array
    {
        return [
            'id' => null,
            'employee' => [
                'name' => $employee->user ? $employee->user->first_name . ' ' . $employee->user->last_name : 'Unknown',
                'department' => $employee->department,
                'position' => $employee->position,
            ],
            'period' => [
                'payroll_period' => $payroll->payroll_period,
                'start_date' => $payroll->start_date->format('Y-m-d'),
                'end_date' => $payroll->end_date->format('Y-m-d'),
            ],
            'earnings' => $previewData['earnings'],
            'deductions' => [
                'paye' => $previewData['deductions']['paye'],
                'statutory' => $previewData['deductions']['statutory_breakdown'] ?? [], // Dynamic Array
                'other_deductions' => $previewData['deductions']['other_deductions'],
                'total_deductions' => $previewData['deductions']['total_deductions'],
            ],
            'summary' => $previewData['summary'],
            'status' => 'pending',
        ];
    }

    /**
     * Format DETAILED Payslip from DB (Hybrid: Legacy + Dynamic)
     */
    private function formatDetailedPayslip(Payslip $payslip): array
    {
        $breakdown = $payslip->breakdown ?? [];
        
        // Try to get dynamic deductions from stored breakdown
        $statutory = $breakdown['deductions_breakdown']['statutory_breakdown'] ?? [];
        
        // If empty (old record), reconstruct basic list from legacy columns
        if (empty($statutory)) {
            if ($payslip->napsa > 0) $statutory[] = ['name' => 'NAPSA', 'amount' => $payslip->napsa];
            if ($payslip->nhima > 0) $statutory[] = ['name' => 'NHIMA', 'amount' => $payslip->nhima];
            if ($payslip->pension > 0) $statutory[] = ['name' => 'Pension', 'amount' => $payslip->pension];
        }

        return [
            'id' => $payslip->id,
            'employee' => [
                'name' => $payslip->employee->user->first_name . ' ' . $payslip->employee->user->last_name,
                'department' => $payslip->employee->department,
                'position' => $payslip->employee->position,
            ],
            'period' => [
                'payroll_period' => $payslip->payroll->payroll_period,
                'start_date' => $payslip->pay_period_start?->format('Y-m-d'),
                'end_date' => $payslip->pay_period_end?->format('Y-m-d'),
            ],
            'earnings' => [
                'basic_salary' => (float) $payslip->basic_salary,
                'house_allowance' => (float) $payslip->house_allowance,
                'transport_allowance' => (float) $payslip->transport_allowance,
                'lunch_allowance' => (float) $payslip->other_allowances,
                'overtime_pay' => (float) $payslip->overtime_pay,
                'bonuses' => (float) $payslip->bonuses,
                'gross_salary' => (float) $payslip->gross_salary,
            ],
            'deductions' => [
                'paye' => (float) $payslip->paye,
                'statutory' => $statutory, // Dynamic List of {name, amount}
                'other_deductions' => (float) $payslip->other_deductions,
                'total_deductions' => (float) $payslip->total_deductions,
            ],
            'summary' => [
                'gross_pay' => (float) $payslip->gross_salary,
                'total_deductions' => (float) $payslip->total_deductions,
                'net_pay' => (float) $payslip->net_pay,
            ],
            'status' => $payslip->status,
            'pdf_available' => !empty($payslip->pdf_path),
        ];
    }

    public function history(Request $request): JsonResponse
    {
        $period = $request->query('payroll_period', Carbon::now()->format('Y-m'));
        
        // Get the payroll for this period
        $payroll = Payroll::where('payroll_period', $period)->first();
        
        $data = [];
        
        if ($payroll) {
            // Get all payslips for this payroll
            $payslips = Payslip::where('payroll_id', $payroll->id)
                ->with(['employee.user', 'payroll'])
                ->get();
            
            // Map payslips to response format
            $data = $payslips->map(function ($ps) {
                return [
                    'employee_id' => $ps->employee_id,
                    'status' => $ps->status ?? 'pending',
                    'pay_period' => $ps->payroll->payroll_period,
                    'amount' => $ps->net_pay,
                    'payslip_id' => $ps->id,
                    'created_at' => $ps->created_at,
                    'updated_at' => $ps->updated_at,
                ];
            })->toArray();
        }
        
        return response()->json([
            'data' => $data,
            'payroll_period' => $period,
            'payroll_id' => $payroll->id ?? null,
        ]);
    }

    public function cycles(Request $request): JsonResponse
    {
        $cycles = Payroll::select('payroll_period')
            ->distinct()
            ->orderBy('payroll_period', 'desc')
            ->get()
            ->pluck('payroll_period');

        return response()->json([
            'cycles' => $cycles
        ]);
    }

    public function show(Payroll $payroll): PayrollResource
    {
        $payroll->load(['payslips.employee.user', 'payslips.payroll']);
        
        return new PayrollResource($payroll);
    }

    public function destroy(Payroll $payroll): JsonResponse
    {
        if ($payroll->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft payrolls can be deleted'
            ], 422);
        }

        $payroll->delete();

        return response()->json([
            'message' => 'Payroll deleted successfully'
        ]);
    }
}