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
use Illuminate\Validation\ValidationException;

class PayrollController extends Controller
{
    public function __construct(private PayrollCalculationService $payrollService)
    {
    }

    public function processPayroll(ProcessPayrollRequest $request): JsonResponse
{
    $validated = $request->validated();
    $period = $validated['payroll_period'];

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
        // If specific employee IDs provided, only process those
        $employeeIds = $validated['employee_ids'] ?? [];
        
        if (empty($employeeIds)) {
            // If no IDs specified, get all employees
            $employeeIds = Employee::pluck('id')->toArray();
        }

        // Process each employee
        $processedCount = 0;
        foreach ($employeeIds as $empId) {
            $employee = Employee::find($empId);
            
            if (!$employee) {
                continue;
            }

            // Check if payslip already exists
            $payslip = Payslip::where('payroll_id', $payroll->id)
                ->where('employee_id', $empId)
                ->first();

            if ($payslip) {
                // Update existing payslip to 'paid'
                $payslip->status = 'paid';
                $payslip->save();
            } else {
                // Create new payslip with 'paid' status
                $this->payrollService->createPayslip($employee, $payroll, 'paid');
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

public function updateStatus(Request $request): JsonResponse
{
    $validated = $request->validate([
        'employee_ids' => 'required|array|min:1',
        'employee_ids.*' => 'integer|exists:employees,id',
        'status' => 'required|in:pending,paid',
        'payroll_period' => 'required|string',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
    ]);

    $period = $validated['payroll_period'];
    
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

        // Find existing payslip
        $payslip = Payslip::where('payroll_id', $payroll->id)
            ->where('employee_id', $empId)
            ->first();

        if ($payslip) {
            // Update existing payslip status
            $payslip->status = $validated['status'];
            $payslip->save();
            $updatedCount++;
        } else {
            // Create new payslip with calculated values
            $basicSalary = (float) ($employee->base_salary ?? 0);
            $overtimePay = 0.0;
            $bonuses = 0.0;
            $grossPay = $basicSalary + $overtimePay + $bonuses;
            $taxDeductions = 0.0;
            $otherDeductions = 0.0;
            $netPay = $grossPay - $taxDeductions - $otherDeductions;

            Payslip::create([
                'employee_id' => $empId,
                'payroll_id' => $payroll->id,
                'basic_salary' => $basicSalary,
                'overtime_pay' => $overtimePay,
                'bonuses' => $bonuses,
                'gross_pay' => $grossPay,
                'tax_deductions' => $taxDeductions,
                'other_deductions' => $otherDeductions,
                'net_pay' => $netPay,
                'status' => $validated['status'], // Use the requested status
                'breakdown' => [
                    'basic_salary' => $basicSalary,
                    'overtime_pay' => $overtimePay,
                    'bonuses' => $bonuses,
                    'tax_deductions' => $taxDeductions,
                    'other_deductions' => $otherDeductions,
                ],
            ]);
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
 * Calculate payslip data for an employee
 */
private function calculatePayslipData(Employee $employee, Payroll $payroll): array
{
    // Use the payroll service to calculate, but return array instead of creating
    $basicSalary = $this->calculateBasicSalary($employee, $payroll);
    $overtimePay = 0.0; // Implement if needed
    $bonuses = 0.0;
    $grossPay = $basicSalary + $overtimePay + $bonuses;
    $taxDeductions = 0.0; // Calculate using tax service if available
    $otherDeductions = 0.0;
    $netPay = $grossPay - $taxDeductions - $otherDeductions;

    return [
        'basic_salary' => $basicSalary,
        'overtime_pay' => $overtimePay,
        'bonuses' => $bonuses,
        'gross_pay' => $grossPay,
        'tax_deductions' => $taxDeductions,
        'other_deductions' => $otherDeductions,
        'net_pay' => $netPay,
        'breakdown' => [
            'basic_salary' => $basicSalary,
            'overtime_pay' => $overtimePay,
            'bonuses' => $bonuses,
            'tax_deductions' => $taxDeductions,
            'other_deductions' => $otherDeductions,
        ],
    ];
}

/**
 * Simple basic salary calculation
 */
private function calculateBasicSalary(Employee $employee, Payroll $payroll): float
{
    return (float) ($employee->base_salary ?? 0);
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
            ->with(['employee', 'payroll'])
            ->get();
        
        // Map payslips to response format
        $data = $payslips->map(function ($ps) {
            return [
                'employee_id' => $ps->employee_id,
                'status' => $ps->status ?? 'pending', // Default to pending if null
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