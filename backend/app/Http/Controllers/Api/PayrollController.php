<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\ProcessPayrollRequest;
use App\Http\Resources\PayrollResource;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Services\PayrollCalculationService;
use App\Models\TaxConfiguration;
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

        // Find or create payroll
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
                
                if (!$employee) {
                    continue;
                }

                // Get adjustments for this employee
                $employeeAdjustments = $adjustments[$empId] ?? [];

                // Check if payslip already exists
                $payslip = Payslip::where('payroll_id', $payroll->id)
                    ->where('employee_id', $empId)
                    ->first();

                if ($payslip) {
                    // Update existing payslip to 'paid' and apply adjustments
                    if (!empty($employeeAdjustments)) {
                        $this->applyAdjustmentsToPayslip($payslip, $employeeAdjustments);
                    }
                    $payslip->status = 'paid';
                    $payslip->save();
                } else {
                    // Create new payslip with 'paid' status and adjustments
                    if (!empty($employeeAdjustments)) {
                        $this->payrollService->createPayslipWithAdjustments($employee, $payroll, 'paid', $employeeAdjustments);
                    } else {
                        $this->payrollService->createPayslip($employee, $payroll, 'paid');
                    }
                }
                
                $processedCount++;
            }

            // Update totals and mark as completed
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
            \Log::error('Payroll processing failed', [
                'error' => $e->getMessage(),
                'payroll_id' => $payroll->id,
                'period' => $period,
            ]);

            if ($payroll->status === 'draft') {
                $payroll->update(['status' => 'failed']);
            }
            
            return response()->json([
                'message' => 'Failed to process payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply adjustments to existing payslip
     */
    private function applyAdjustmentsToPayslip(Payslip $payslip, array $adjustments): void
    {
        $bonuses = ($adjustments['overtime_bonus'] ?? 0) + ($adjustments['other_bonuses'] ?? 0);
        $deductions = ($adjustments['loan_deductions'] ?? 0) + ($adjustments['advance_deductions'] ?? 0);
        
        // Update gross salary with bonuses
        $payslip->gross_salary += $bonuses;
        $payslip->bonuses += $bonuses;
        
        // Update other_deductions with additional deductions
        $payslip->other_deductions += $deductions;
        
        // Recalculate total deductions and net pay
        $payslip->total_deductions = $payslip->paye + $payslip->napsa + $payslip->nhima + $payslip->other_deductions;
        $payslip->net_pay = $payslip->gross_salary - $payslip->total_deductions;
        
        // Store adjustments in breakdown
        $breakdown = $payslip->breakdown ?? [];
        $breakdown['adjustments'] = $adjustments;
        $breakdown['adjustments_applied'] = [
            'total_bonuses' => $bonuses,
            'total_additional_deductions' => $deductions,
            'net_effect' => $bonuses - $deductions
        ];
        
        $payslip->breakdown = $breakdown;
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

    /**
     * Get payroll summary for employees in a period with pre-calculated values
     * This endpoint ensures all calculations are done backend-side
     */
    public function employeesSummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payroll_period' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'per_page' => 'integer|min:1|max:1000',
        ]);
    
        $period = $validated['payroll_period'];
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];
    
        // Find or create payroll for the period
        $payroll = Payroll::firstOrCreate(
            ['payroll_period' => $period],
            [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'draft',
            ]
        );
    
        // Get all employees
        $employees = Employee::with('user')->get();
    
        $summaryData = [];
        foreach ($employees as $employee) {
            // Find existing payslip
            $payslip = Payslip::where('payroll_id', $payroll->id)
                ->where('employee_id', $employee->id)
                ->first();
    
            if ($payslip) {
                // Use existing payslip data
                $payslipData = $this->formatDetailedPayslip($payslip);
                $payrollStatus = $payslip->status ?? 'pending';
            } else {
                // Generate preview data (no save)
                $previewData = $this->payrollService->generatePayslipPreview($employee, $payroll);
                $payslipData = $this->formatPreviewPayslip($previewData, $employee, $payroll);
                $payrollStatus = 'pending'; // Default for preview
            }
    
            $summaryData[] = [
                'id' => $employee->id,
                'name' => ($employee->user ? $employee->user->first_name . ' ' . $employee->user->last_name : 'Unknown Employee'),
                'email' => $employee->user?->email,
                'position' => $employee->position ?? $employee->department ?? 'Unassigned',
                'base_salary' => (float) ($employee->base_salary ?? 0),
                'transport_allowance' => (float) ($employee->transport_allowance ?? 0),
                'lunch_allowance' => (float) ($employee->lunch_allowance ?? 0),
                'gross_salary' => (float) ($payslipData['summary']['gross_pay'] ?? 0),
                'net_pay' => (float) ($payslipData['summary']['net_pay'] ?? 0),
                'payroll_status' => $payrollStatus,
                'pay_period' => $period,
                'payslip_data' => $payslipData,
                'is_preview' => !isset($payslip),
            ];
        }
    
        return response()->json([
            'data' => $summaryData,
            'payroll_period' => $period,
            'payroll_id' => $payroll->id,
            'total_employees' => count($summaryData),
        ]);
    }

    /**
     * Format preview payslip data to match detailed format
     */
    private function formatPreviewPayslip(array $previewData, Employee $employee, Payroll $payroll): array
    {
        return [
            'id' => null, // No ID for preview
            'employee' => [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'name' => $employee->user ? $employee->user->first_name . ' ' . $employee->user->last_name : 'Unknown Employee',
                'email' => $employee->user?->email,
                'department' => $employee->department,
                'position' => $employee->position,
            ],
            'period' => [
                'payroll_period' => $payroll->payroll_period,
                'start_date' => $payroll->start_date->format('Y-m-d'),
                'end_date' => $payroll->end_date->format('Y-m-d'),
                'payment_date' => $payroll->end_date->format('Y-m-d'),
            ],
            'earnings' => $previewData['earnings'] ?? [],
            'deductions' => $previewData['deductions'] ?? [],
            'summary' => $previewData['summary'] ?? [],
            'status' => 'pending',
            'calculation_breakdown' => $previewData['breakdown'] ?? [],
            'pdf_available' => false,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'is_preview' => true,
        ];
    }

    /**
     * Get detailed view of employee payslip for a specific period
     */
    public function viewEmployeePayslip(Request $request, int $employeeId): JsonResponse
    {
        $validated = $request->validate([
            'payroll_period' => 'required|string',
        ]);

        $employee = Employee::with('user')->findOrFail($employeeId);
        
        // Find payroll for the period
        $payroll = Payroll::where('payroll_period', $validated['payroll_period'])->first();
        if (!$payroll) {
            return response()->json([
                'message' => 'No payroll found for this period',
                'data' => null
            ], 404);
        }

        // Find or generate payslip data
        $payslip = Payslip::where('payroll_id', $payroll->id)
            ->where('employee_id', $employeeId)
            ->first();

        if (!$payslip) {
            // Generate preview without saving
            $payslipData = $this->payrollService->generatePayslipPreview($employee, $payroll);
            
            return response()->json([
                'message' => 'Payslip preview generated (not yet processed)',
                'data' => $this->formatPreviewPayslip($payslipData, $employee, $payroll),
                'is_preview' => true
            ]);
        }

        // Return actual payslip with full breakdown
        $formattedPayslip = $this->formatDetailedPayslip($payslip);
        return response()->json([
            'data' => $formattedPayslip,
            'is_preview' => false
        ]);
    }

    /**
     * Format detailed payslip with full breakdown
     */
    private function formatDetailedPayslip(Payslip $payslip): array
    {
        $breakdown = $payslip->breakdown ?? [];
        
        return [
            'id' => $payslip->id,
            'employee' => [
                'id' => $payslip->employee->id,
                'employee_id' => $payslip->employee->employee_id,
                'name' => $payslip->employee->user->first_name . ' ' . $payslip->employee->user->last_name,
                'email' => $payslip->employee->user->email,
                'department' => $payslip->employee->department,
                'position' => $payslip->employee->position,
            ],
            'period' => [
                'payroll_period' => $payslip->payroll->payroll_period,
                'start_date' => $payslip->pay_period_start?->format('Y-m-d'),
                'end_date' => $payslip->pay_period_end?->format('Y-m-d'),
                'payment_date' => $payslip->payment_date?->format('Y-m-d'),
            ],
            'earnings' => [
                'basic_salary' => (float) $payslip->basic_salary,
                'house_allowance' => (float) $payslip->house_allowance,
                'transport_allowance' => (float) $payslip->transport_allowance,
                'lunch_allowance' => (float) $payslip->other_allowances,
                'overtime_hours' => (float) $payslip->overtime_hours,
                'overtime_rate' => (float) $payslip->overtime_rate,
                'overtime_pay' => (float) $payslip->overtime_pay,
                'bonuses' => (float) ($payslip->bonuses ?? 0),
                'gross_salary' => (float) $payslip->gross_salary,
            ],
            'deductions' => [
                'paye' => (float) $payslip->paye,
                'napsa' => (float) $payslip->napsa,
                'nhima' => (float) $payslip->nhima,
                'other_deductions' => (float) $payslip->other_deductions,
                'total_deductions' => (float) $payslip->total_deductions,
            ],
            'summary' => [
                'gross_pay' => (float) $payslip->gross_salary,
                'total_deductions' => (float) $payslip->total_deductions,
                'net_pay' => (float) $payslip->net_pay,
            ],
            'status' => $payslip->status,
            'calculation_breakdown' => $breakdown,
            'pdf_available' => !empty($payslip->pdf_path) && \Storage::exists($payslip->pdf_path),
            'created_at' => $payslip->created_at->format('Y-m-d H:i:s'),
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