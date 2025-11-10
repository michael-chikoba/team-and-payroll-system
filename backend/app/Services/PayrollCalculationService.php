<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\TaxConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PayrollCalculationService
{
    public function __construct(
        private TaxCalculationService $taxService,
        private AttendanceService $attendanceService
    ) {
    }

    public function processPayroll(Payroll $payroll, array $employeeIds = []): Payroll
    {
        $payroll->markAsProcessing();
         
        $employees = $this->getEmployeesForPayroll($payroll, $employeeIds);
        $existingPayslipEmployeeIds = Payslip::where('payroll_id', $payroll->id)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->pluck('employee_id');
        $employeesToProcess = $employees->whereNotIn('id', $existingPayslipEmployeeIds);

        $totalGross = 0;
        $totalNet = 0;
        $processedCount = 0;
        foreach ($employeesToProcess as $employee) {
            try {
                $payslip = $this->calculateEmployeePay($employee, $payroll, 'paid');
                $totalGross += $payslip->gross_pay;
                $totalNet += $payslip->net_pay;
                $processedCount++;
            } catch (\Exception $e) {
                // Log error and continue with other employees
                \Log::error("Payroll calculation failed for employee {$employee->id}: " . $e->getMessage());
            }
        }

        $this->updatePayrollTotals($payroll);
        $payroll->status = 'completed';
        $payroll->processed_at = now();
        $payroll->save();

        return $payroll->fresh();
    }

    public function createPayslip(Employee $employee, Payroll $payroll, string $status = 'paid'): ?Payslip
    {
        try {
            $payslip = $this->calculateEmployeePay($employee, $payroll, $status);
            return $payslip;
        } catch (\Exception $e) {
            \Log::error("Failed to create payslip for employee {$employee->id}: " . $e->getMessage());
            return null;
        }
    }

    public function updatePayrollTotals(Payroll $payroll): void
    {
        $payslips = $payroll->payslips()->get();
        $totalGross = $payslips->sum('gross_pay');
        $totalNet = $payslips->sum('net_pay');
        $employeeCount = $payslips->count();

        $payroll->update([
            'total_gross' => $totalGross,
            'total_net' => $totalNet,
            'employee_count' => $employeeCount,
        ]);
    }

    private function getEmployeesForPayroll(Payroll $payroll, array $employeeIds = []): Collection
    {
        $query = Employee::with(['user', 'attendances' => function ($query) use ($payroll) {
            $query->whereBetween('date', [$payroll->start_date, $payroll->end_date]);
        }]);
        if (!empty($employeeIds)) {
            $query->whereIn('id', $employeeIds);
        }
        return $query->get();
    }

    private function calculateEmployeePay(Employee $employee, Payroll $payroll, string $status = 'paid'): Payslip
    {
        // Calculate basic salary for the period
        $basicSalary = $this->calculateBasicSalary($employee, $payroll);
         
        // Calculate overtime
        $overtimePay = $this->calculateOvertimePay($employee, $payroll);
         
        // Calculate bonuses
        $bonuses = $this->calculateBonuses($employee, $payroll);
         
        // Calculate gross pay
        $grossPay = $basicSalary + $overtimePay + $bonuses;
         
        // Calculate deductions
        $taxDeductions = $this->taxService->calculateTax($grossPay, $employee);
        $otherDeductions = $this->calculateOtherDeductions($employee, $payroll);
         
        // Calculate net pay
        $netPay = $grossPay - $taxDeductions - $otherDeductions;
        return Payslip::create([
            'employee_id' => $employee->id,
            'payroll_id' => $payroll->id,
            'basic_salary' => $basicSalary,
            'overtime_pay' => $overtimePay,
            'bonuses' => $bonuses,
            'gross_pay' => $grossPay,
            'tax_deductions' => $taxDeductions,
            'other_deductions' => $otherDeductions,
            'net_pay' => $netPay,
            'status' => $status,
            'breakdown' => [
                'basic_salary' => $basicSalary,
                'overtime_pay' => $overtimePay,
                'bonuses' => $bonuses,
                'tax_deductions' => $taxDeductions,
                'other_deductions' => $otherDeductions,
            ],
        ]);
    }

    private function calculateBasicSalary(Employee $employee, Payroll $payroll): float
    {
        $startDate = Carbon::parse($payroll->start_date);
        $endDate = Carbon::parse($payroll->end_date);
         
        // For monthly payroll, return full base salary
        if ($payroll->payroll_period === 'monthly') {
            return (float) $employee->base_salary;
        }
         
        // For bi-weekly, calculate pro-rated amount
        $daysInMonth = $startDate->daysInMonth;
        $workingDays = $this->getWorkingDaysInPeriod($startDate, $endDate);
         
        return (float) ($employee->base_salary / $daysInMonth * $workingDays);
    }

    private function calculateOvertimePay(Employee $employee, Payroll $payroll): float
    {
        $overtimeHours = $this->attendanceService->getOvertimeHours(
            $employee->id,
            $payroll->start_date,
            $payroll->end_date
        );
         
        $overtimeRate = $employee->base_salary / 160 * 1.5; // Assuming 160 hours monthly
         
        return (float) ($overtimeHours * $overtimeRate);
    }

    private function calculateBonuses(Employee $employee, Payroll $payroll): float
    {
        // Implement bonus calculation logic
        // This could be based on performance, attendance, etc.
        return 0.0;
    }

    private function calculateOtherDeductions(Employee $employee, Payroll $payroll): float
    {
        // Implement other deductions logic
        // This could include insurance, retirement contributions, etc.
        return 0.0;
    }

    private function getWorkingDaysInPeriod(Carbon $startDate, Carbon $endDate): int
    {
        $days = 0;
        $current = $startDate->copy();
         
        while ($current <= $endDate) {
            if (!$current->isWeekend()) {
                $days++;
            }
            $current->addDay();
        }
         
        return $days;
    }
}