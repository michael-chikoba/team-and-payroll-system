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
use Illuminate\Support\Facades\Log;

class PayrollController extends Controller
{
    public function __construct(private PayrollCalculationService $payrollService) {}

    /**
     * Get business-scoped employees query for payroll
     */
    private function getBusinessScopedEmployees(Request $request)
    {
        $user = $request->user();
        $requestedBusinessId = $request->input('business_id');
        $query = Employee::query();

        Log::info('PAYROLL_CONTROLLER: Getting business scoped employees', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'user_business_id' => $user->current_business_id,
            'requested_business_id' => $requestedBusinessId,
        ]);

        if ($user->role === 'admin') {
            if ($user->current_business_id) {
                if ($requestedBusinessId && (int)$requestedBusinessId !== $user->current_business_id) {
                    if (!$user->businesses()->where('businesses.id', $requestedBusinessId)->exists()) {
                        Log::warning('PAYROLL_CONTROLLER: Admin attempting to access unauthorized business', [
                            'admin_id' => $user->id,
                            'admin_business_id' => $user->current_business_id,
                            'requested_business_id' => $requestedBusinessId,
                        ]);
                        $query->where('business_id', 0);
                        return $query;
                    }
                }
                $businessId = $requestedBusinessId ?: $user->current_business_id;
                $query->where('business_id', $businessId);
                Log::info('PAYROLL_CONTROLLER: Admin filtering by business', [
                    'admin_id' => $user->id,
                    'business_id' => $businessId,
                ]);
            } elseif ($user->businesses()->exists()) {
                if ($requestedBusinessId) {
                    if ($user->businesses()->where('businesses.id', $requestedBusinessId)->exists()) {
                        $query->where('business_id', $requestedBusinessId);
                    } else {
                        $query->where('business_id', 0);
                        return $query;
                    }
                } else {
                    $businessIds = $user->businesses()->pluck('businesses.id');
                    $query->whereIn('business_id', $businessIds);
                }
            } else {
                if ($requestedBusinessId) {
                    $query->where('business_id', $requestedBusinessId);
                }
            }
        } elseif ($user->role === 'manager') {
            $managerEmployee = Employee::where('user_id', $user->id)->first();
            if ($managerEmployee && $managerEmployee->business_id) {
                $query->where('business_id', $managerEmployee->business_id)
                      ->where('manager_id', $user->id);
            } else {
                $query->where('manager_id', $user->id);
            }
        } elseif ($user->role === 'employee') {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public function processPayroll(ProcessPayrollRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $period = $validated['payroll_period'];
        $adjustments = $validated['adjustments'] ?? [];
        $user = $request->user();

        Log::info('PAYROLL_CONTROLLER: Processing payroll', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'user_business_id' => $user->current_business_id,
            'payroll_period' => $period,
        ]);

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
            $scopedEmployeesQuery = $this->getBusinessScopedEmployees($request);

            if (empty($employeeIds)) {
                $employeeIds = $scopedEmployeesQuery->pluck('id')->toArray();
            } else {
                $authorizedIds = $scopedEmployeesQuery->pluck('id')->toArray();
                $employeeIds = array_intersect($employeeIds, $authorizedIds);
            }

            if (empty($employeeIds)) {
                return response()->json([
                    'message' => 'No authorized employees found for payroll processing'
                ], 403);
            }

            $processedCount = 0;
            foreach ($employeeIds as $empId) {
                $employee = Employee::find($empId);
                if (!$employee) continue;

                if ($user->role === 'admin' && $user->current_business_id) {
                    if ($employee->business_id !== $user->current_business_id) continue;
                }

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

            Log::info('PAYROLL_CONTROLLER: Payroll processed successfully', [
                'user_id' => $user->id,
                'payroll_id' => $payroll->id,
                'processed_count' => $processedCount,
            ]);

            return response()->json([
                'payroll' => new PayrollResource($payroll->fresh()),
                'message' => "Successfully processed payroll for {$processedCount} employee(s)",
                'processed_count' => $processedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('PAYROLL_CONTROLLER: Payroll processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Failed to process payroll: ' . $e->getMessage()], 500);
        }
    }

   private function applyAdjustmentsToPayslip(Payslip $payslip, array $adjustments): void
{
    $bonuses = ($adjustments['overtime_bonus'] ?? 0) + ($adjustments['other_bonuses'] ?? 0);
    $deductions = ($adjustments['loan_deductions'] ?? 0) + ($adjustments['advance_deductions'] ?? 0);

    // These will auto-decrypt via the trait when accessed
    $currentGross = $payslip->gross_salary;
    $currentBonuses = $payslip->bonuses;
    $currentOtherDeductions = $payslip->other_deductions;
    $currentPaye = $payslip->paye;

    // Calculate new values
    $newGross = $currentGross + $bonuses;
    $newBonuses = $currentBonuses + $bonuses;
    $newOtherDeductions = $currentOtherDeductions + $deductions;

    // Get statutory total from breakdown or legacy fields
    $statutoryTotal = 0;
    if (isset($payslip->breakdown['deductions_breakdown']['statutory_total'])) {
        $statutoryTotal = $payslip->breakdown['deductions_breakdown']['statutory_total'];
    } else {
        $statutoryTotal = ($payslip->napsa ?? 0) + ($payslip->nhima ?? 0) + ($payslip->pension ?? 0);
    }

    $newTotalDeductions = $currentPaye + $statutoryTotal + $newOtherDeductions;
    $newNetPay = $newGross - $newTotalDeductions;

    // Update the model - these will be auto-encrypted on save
    $payslip->gross_salary = $newGross;
    $payslip->bonuses = $newBonuses;
    $payslip->other_deductions = $newOtherDeductions;
    $payslip->total_deductions = $newTotalDeductions;
    $payslip->net_pay = $newNetPay;

    $breakdown = $payslip->breakdown ?? [];
    $breakdown['adjustments'] = $adjustments;
    $payslip->breakdown = $breakdown;
}

    public function employeesSummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payroll_period' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'per_page' => 'integer|min:1|max:1000',
            'business_id' => 'nullable|integer',
        ]);

        $user = $request->user();
        $period = $validated['payroll_period'];

        Log::info('PAYROLL_CONTROLLER: Fetching employees summary', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'user_business_id' => $user->current_business_id,
            'requested_business_id' => $validated['business_id'] ?? null,
            'payroll_period' => $period,
        ]);

        $payroll = Payroll::firstOrCreate(['payroll_period' => $period], [
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'draft',
        ]);

        $employees = $this->getBusinessScopedEmployees($request)
            ->with(['user', 'business'])
            ->get();

        Log::info('PAYROLL_CONTROLLER: Employees fetched for summary', [
            'user_id' => $user->id,
            'employee_count' => $employees->count(),
        ]);

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
                'email' => $employee->user ? $employee->user->email : null,
                'position' => $employee->position ?? 'Unassigned',
                'business_id' => $employee->business_id,
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
            'meta' => [
                'total' => count($summaryData),
                'user_business_id' => $user->current_business_id,
            ]
        ]);
    }

    /**
     * Update payroll status for one or more employees.
     *
     * KEY BEHAVIOUR CHANGE:
     * When reverting to 'pending', we DELETE the existing payslip entirely
     * rather than just updating the status field. This forces a full
     * recalculation the next time the employee is processed, preventing
     * stale or incorrectly-calculated values from persisting.
     */
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

        $user = $request->user();
        $period = $validated['payroll_period'];
        $adjustments = $validated['adjustments'] ?? [];
        $isReverting = $validated['status'] === 'pending';

        Log::info('PAYROLL_CONTROLLER: Updating payroll status', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'user_business_id' => $user->current_business_id,
            'employee_count' => count($validated['employee_ids']),
            'target_status' => $validated['status'],
            'is_reverting' => $isReverting,
        ]);

        $scopedEmployeesQuery = $this->getBusinessScopedEmployees($request);
        $authorizedIds = $scopedEmployeesQuery->pluck('id')->toArray();
        $employeeIds = array_intersect($validated['employee_ids'], $authorizedIds);

        if (empty($employeeIds)) {
            return response()->json(['message' => 'No authorized employees found to update'], 403);
        }

        $payroll = Payroll::firstOrCreate(
            ['payroll_period' => $period],
            [
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => 'draft',
            ]
        );

        $updatedCount = 0;
        $deletedCount = 0;
        $createdCount = 0;

        foreach ($employeeIds as $empId) {
            $employee = Employee::find($empId);
            if (!$employee) continue;

            if ($user->role === 'admin' && $user->current_business_id) {
                if ($employee->business_id !== $user->current_business_id) continue;
            }

            // Find ALL payslips for this employee in this payroll period
            // (catches duplicates too)
            $existingPayslips = Payslip::where('payroll_id', $payroll->id)
                ->where('employee_id', $empId)
                ->get();

            // ----------------------------------------------------------------
            // REVERT TO PENDING — delete all payslips for this employee
            // so the next process run does a full fresh recalculation.
            // ----------------------------------------------------------------
            if ($isReverting) {
                if ($existingPayslips->isNotEmpty()) {
                    $deleteCount = $existingPayslips->count();
                    Payslip::where('payroll_id', $payroll->id)
                        ->where('employee_id', $empId)
                        ->delete();

                    $deletedCount += $deleteCount;

                    Log::info('PAYROLL_CONTROLLER: Payslip(s) deleted on revert to pending', [
                        'employee_id' => $empId,
                        'payroll_id' => $payroll->id,
                        'deleted_count' => $deleteCount,
                    ]);
                } else {
                    Log::info('PAYROLL_CONTROLLER: No payslip found to delete on revert', [
                        'employee_id' => $empId,
                        'payroll_id' => $payroll->id,
                    ]);
                }

                $updatedCount++;
                continue; // Skip to next employee — no payslip creation needed
            }

            // ----------------------------------------------------------------
            // MARK AS PAID — update existing payslip or create a new one
            // ----------------------------------------------------------------
            $payslip = $existingPayslips->first();
            $employeeAdjustments = $adjustments[$empId] ?? [];

            if ($payslip) {
                if (!empty($employeeAdjustments)) {
                    $this->applyAdjustmentsToPayslip($payslip, $employeeAdjustments);
                }
                $payslip->status = 'paid';
                $payslip->save();
                $updatedCount++;
            } else {
                if (!empty($employeeAdjustments)) {
                    $this->payrollService->createPayslipWithAdjustments($employee, $payroll, 'paid', $employeeAdjustments);
                } else {
                    $this->payrollService->createPayslip($employee, $payroll, 'paid');
                }
                $createdCount++;
            }
        }

        // Always recalculate payroll totals after any change
        $this->payrollService->updatePayrollTotals($payroll);

        // If reverting caused all payslips to be removed, set payroll back to draft
        $remainingPayslips = Payslip::where('payroll_id', $payroll->id)->count();

        if ($isReverting) {
            if ($remainingPayslips === 0) {
                $payroll->status = 'draft';
                $payroll->processed_at = null;
                $payroll->save();

                Log::info('PAYROLL_CONTROLLER: Payroll reset to draft — no payslips remaining', [
                    'payroll_id' => $payroll->id,
                ]);
            } else {
                // Some employees are still paid, keep completed status
                Log::info('PAYROLL_CONTROLLER: Payroll still has paid payslips after revert', [
                    'payroll_id' => $payroll->id,
                    'remaining_payslips' => $remainingPayslips,
                ]);
            }
        } else {
            // Marking as paid — ensure payroll is completed
            if ($payroll->status === 'draft') {
                $payroll->status = 'completed';
                $payroll->processed_at = now();
                $payroll->save();
            }
        }

        $message = $isReverting
            ? sprintf('Successfully reverted %d employee(s) to pending. %d payslip(s) removed for recalculation.', $updatedCount, $deletedCount)
            : sprintf('Successfully processed: %d updated, %d created.', $updatedCount, $createdCount);

        Log::info('PAYROLL_CONTROLLER: Status update completed', [
            'user_id' => $user->id,
            'target_status' => $validated['status'],
            'updated_count' => $updatedCount,
            'deleted_count' => $deletedCount,
            'created_count' => $createdCount,
            'remaining_payslips' => $remainingPayslips,
        ]);

        return response()->json([
            'message' => $message,
            'updated_count' => $updatedCount,
            'deleted_count' => $deletedCount,
            'created_count' => $createdCount,
            'payroll_id' => $payroll->id,
        ]);
    }

    public function viewEmployeePayslip(Request $request, int $employeeId): JsonResponse
    {
        $user = $request->user();
        $payrollPeriod = $request->query('payroll_period');

        $scopedEmployees = $this->getBusinessScopedEmployees($request)->pluck('id')->toArray();

        if (!in_array($employeeId, $scopedEmployees)) {
            return response()->json(['message' => 'Unauthorized access to employee payslip'], 403);
        }

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
                'statutory' => $previewData['deductions']['statutory_breakdown'] ?? [],
                'other_deductions' => $previewData['deductions']['other_deductions'],
                'total_deductions' => $previewData['deductions']['total_deductions'],
            ],
            'summary' => $previewData['summary'],
            'status' => 'pending',
        ];
    }

    private function formatDetailedPayslip(Payslip $payslip): array
{
    $breakdown = $payslip->breakdown ?? [];
    $statutory = $breakdown['deductions_breakdown']['statutory_breakdown'] ?? [];

    if (empty($statutory)) {
        // These will auto-decrypt when accessed
        if ($payslip->napsa > 0) $statutory[] = ['name' => 'NAPSA', 'amount' => (float) $payslip->napsa];
        if ($payslip->nhima > 0) $statutory[] = ['name' => 'NHIMA', 'amount' => (float) $payslip->nhima];
        if ($payslip->pension > 0) $statutory[] = ['name' => 'Pension', 'amount' => (float) $payslip->pension];
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
            'basic_salary' => (float) $payslip->basic_salary, // Auto-decrypts
            'house_allowance' => (float) $payslip->house_allowance,
            'transport_allowance' => (float) $payslip->transport_allowance,
            'lunch_allowance' => (float) $payslip->other_allowances,
            'overtime_pay' => (float) $payslip->overtime_pay,
            'bonuses' => (float) $payslip->bonuses,
            'gross_salary' => (float) $payslip->gross_salary, // Auto-decrypts
        ],
        'deductions' => [
            'paye' => (float) $payslip->paye,
            'statutory' => $statutory,
            'other_deductions' => (float) $payslip->other_deductions,
            'total_deductions' => (float) $payslip->total_deductions, // Auto-decrypts
        ],
        'summary' => [
            'gross_pay' => (float) $payslip->gross_salary, // Auto-decrypts
            'total_deductions' => (float) $payslip->total_deductions, // Auto-decrypts
            'net_pay' => (float) $payslip->net_pay, // Auto-decrypts
        ],
        'status' => $payslip->status,
        'pdf_available' => !empty($payslip->pdf_path),
    ];
}

   public function history(Request $request): JsonResponse
{
    $user = $request->user();
    $period = $request->query('payroll_period', Carbon::now()->format('Y-m'));

    $payroll = Payroll::where('payroll_period', $period)->first();
    $data = [];

    if ($payroll) {
        $scopedEmployeeIds = $this->getBusinessScopedEmployees($request)->pluck('id')->toArray();

        $payslips = Payslip::where('payroll_id', $payroll->id)
            ->whereIn('employee_id', $scopedEmployeeIds)
            ->with(['employee.user', 'payroll'])
            ->get();

        $data = $payslips->map(function ($ps) {
            return [
                'employee_id' => $ps->employee_id,
                'status' => $ps->status ?? 'pending',
                'pay_period' => $ps->payroll->payroll_period,
                'amount' => (float) $ps->net_pay, // Auto-decrypts
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

        return response()->json(['cycles' => $cycles]);
    }

    public function show(Payroll $payroll): PayrollResource
    {
        $payroll->load(['payslips.employee.user', 'payslips.payroll']);
        return new PayrollResource($payroll);
    }

    public function destroy(Payroll $payroll): JsonResponse
    {
        if ($payroll->status !== 'draft') {
            return response()->json(['message' => 'Only draft payrolls can be deleted'], 422);
        }
        $payroll->delete();
        return response()->json(['message' => 'Payroll deleted successfully']);
    }

    
}