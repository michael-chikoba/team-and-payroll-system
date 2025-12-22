<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\GeneratePayslipRequest;
use App\Jobs\SendPayslipEmail;
use App\Models\Payslip;
use App\Models\Payroll;
use App\Models\Employee;
use App\Services\PayslipGeneratorService;
use App\Models\TaxConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PayslipController extends Controller
{
    public function __construct(private PayslipGeneratorService $payslipGenerator) {}

    /**
     * List payslips for employees (filtered by current user if employee)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;
        
        if (!$employee) {
            return response()->json([
                'message' => 'Employee record not found',
                'data' => []
            ], 404);
        }
        
        $query = Payslip::where('employee_id', $employee->id)
            ->with(['employee.user', 'employee.business'])
            ->orderBy('created_at', 'desc');
        
        // Apply filters
        if ($request->has('year')) {
            $query->whereYear('created_at', $request->year);
        }
        if ($request->has('month')) {
            $query->whereMonth('created_at', $request->month);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $payslips = $query->get();
        
        // Format response
        $formatted = $payslips->map(function ($payslip) {
            return $this->formatPayslipForList($payslip);
        });
        
        return response()->json(['data' => $formatted]);
    }

    /**
     * List all payslips for admin with filters
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Payslip::with(['employee.user', 'employee.business']);
        
        // Filter by Business (if applicable)
        if ($request->has('business_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('business_id', $request->business_id);
            });
        } elseif ($request->user()->current_business_id) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('business_id', $request->user()->current_business_id);
            });
        }

        // Filter by Pay Period
        if ($request->has('pay_period')) {
            $period = $request->pay_period;
            $now = now();
            
            if ($period === 'current') {
                $query->whereMonth('pay_period_start', $now->month)
                    ->whereYear('pay_period_start', $now->year);
            } elseif ($period === 'last') {
                $lastMonth = $now->copy()->subMonth();
                $query->whereMonth('pay_period_start', $lastMonth->month)
                    ->whereYear('pay_period_start', $lastMonth->year);
            }
        }
        
        if ($request->has('start') && $request->has('end')) {
            $query->whereBetween('pay_period_start', [$request->start, $request->end]);
        }
        
        if ($request->has('department')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department', $request->department);
            });
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $payslips = $query->orderBy('created_at', 'desc')->get();
        
        $formatted = $payslips->map(function ($payslip) {
            return $this->formatPayslipForList($payslip);
        });
        
        return response()->json(['data' => $formatted]);
    }

    /**
     * Helper to format list response
     */
    private function formatPayslipForList($payslip)
    {
        $period = 'N/A';
        if ($payslip->pay_period_start) {
            $period = $payslip->pay_period_start->format('M Y');
        } elseif ($payslip->created_at) {
            $period = $payslip->created_at->format('M Y');
        }

        return [
            'id' => $payslip->id,
            'employee_id' => $payslip->employee->employee_id ?? 'N/A',
            'employee_name' => ($payslip->employee->user->first_name ?? '') . ' ' . ($payslip->employee->user->last_name ?? ''),
            'department' => $payslip->employee->department ?? 'N/A',
            'business_name' => $payslip->employee->business->name ?? 'N/A',
            'period' => $period,
            'pay_period_start' => $payslip->pay_period_start?->format('Y-m-d'),
            'pay_period_end' => $payslip->pay_period_end?->format('Y-m-d'),
            'payment_date' => $payslip->payment_date?->format('Y-m-d'),
            'basic_salary' => (float) $payslip->basic_salary,
            'gross_salary' => (float) $payslip->gross_salary,
            'net_pay' => (float) $payslip->net_pay,
            'status' => $payslip->status ?? 'generated',
            'is_sent' => $payslip->is_sent ?? false,
            'pdf_available' => !empty($payslip->pdf_path) && Storage::exists($payslip->pdf_path),
            'pdf_path' => $payslip->pdf_path,
        ];
    }

    /**
     * Create a new payslip (Single Generation)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'pay_period_start' => 'required|date',
            'pay_period_end' => 'required|date|after_or_equal:pay_period_start',
            'payment_date' => 'required|date',
            'basic_salary' => 'required|numeric|min:0',
            'overtime_hours' => 'nullable|numeric|min:0',
            'overtime_rate' => 'nullable|numeric|min:0',
            'generate_pdf' => 'nullable|boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        
        // 1. Get Employee & Context
        $employee = Employee::with('business')->find($request->employee_id);
        if (!$employee) return response()->json(['message' => 'Employee not found'], 404);
        
        $businessId = $employee->business_id;
        $countryCode = $employee->business->country_code ?? 'ZM';

        // 2. Get Dynamic Tax Configuration
        // FIX: Replaced forLocation with getForBusinessAndCountry
        $taxConfig = TaxConfiguration::getForBusinessAndCountry($businessId, $countryCode);
        
        if (!$taxConfig) {
            return response()->json(['message' => 'No active tax configuration found for this employee/business'], 500);
        }
        
        // 3. Prepare Input Data
        $basicSalary = (float) $request->basic_salary;
        $overtimeHours = (float) ($request->overtime_hours ?? 0);
        $overtimeRate = (float) ($request->overtime_rate ?? 0);
        $overtimePay = $overtimeHours * $overtimeRate;
        $bonuses = 0.0; // Add bonus field to request if needed in future

        // 4. Calculate Allowances
        $housing = 0;
        if (!empty($taxConfig->config_data['includeHousingAllowance']) && $taxConfig->config_data['includeHousingAllowance'] === true) {
             $housing = $basicSalary * 0.25; 
        }
        $transport = (float) $employee->transport_allowance;
        $lunch = (float) $employee->lunch_allowance;
        $totalAllowances = $housing + $transport + $lunch;

        // 5. Calculate Gross
        $grossSalary = $basicSalary + $totalAllowances + $overtimePay + $bonuses;

        // 6. Calculate Deductions (Dynamic)
        // 6a. Statutory
        $statutory = $taxConfig->calculateStatutoryDeductions($employee, $basicSalary, $grossSalary);
        
        // 6b. Taxable Income (Reduce by pension types)
        $taxableIncome = $grossSalary;
        foreach($statutory['breakdown'] as $item) {
            if ($item['type'] === 'pension') {
                $taxableIncome -= $item['amount'];
            }
        }

        // 6c. PAYE
        $paye = $taxConfig->calculatePAYE($taxableIncome);
        
        // 6d. Totals
        $otherDeductions = 0;
        $totalDeductions = $paye + $statutory['total_employee'] + $otherDeductions;
        $netSalary = $grossSalary - $totalDeductions;

        // 7. Prepare Breakdown Array (For DB Storage)
        $breakdown = [
            'calculation_method' => 'Dynamic Tax Config',
            'tax_config_id' => $taxConfig->id,
            'earnings_breakdown' => [
                'basic_salary' => $basicSalary,
                'allowances' => [
                    'housing' => $housing,
                    'transport' => $transport,
                    'lunch' => $lunch
                ],
                'overtime' => ['pay' => $overtimePay, 'hours' => $overtimeHours],
                'gross_total' => $grossSalary
            ],
            'deductions_breakdown' => [
                'paye' => $paye,
                'statutory_breakdown' => $statutory['breakdown'], // Dynamic list
                'statutory_total' => $statutory['total_employee'],
                'other_deductions' => $otherDeductions,
                'total_deductions' => $totalDeductions
            ],
            'net_calculation' => [
                'gross' => $grossSalary,
                'deductions' => $totalDeductions,
                'net' => $netSalary
            ]
        ];

        // 8. Map to Legacy Columns (Optional but good for table view consistency)
        $statCollection = collect($statutory['breakdown']);
        $napsaAmount = $statCollection->filter(fn($i) => stripos($i['name'], 'NAPSA') !== false)->sum('amount');
        $nhimaAmount = $statCollection->filter(fn($i) => stripos($i['name'], 'NHIMA') !== false)->sum('amount');
        $pensionAmount = $statCollection->filter(fn($i) => $i['type'] === 'pension' && stripos($i['name'], 'NAPSA') === false)->sum('amount');

        // 9. Create Payslip Record
        $payslip = Payslip::create([
            'employee_id' => $employee->id,
            'payroll_id' => null, // Standalone
            'pay_period_start' => Carbon::parse($request->pay_period_start)->startOfDay(),
            'pay_period_end' => Carbon::parse($request->pay_period_end)->startOfDay(),
            'payment_date' => Carbon::parse($request->payment_date)->startOfDay(),
            'basic_salary' => $basicSalary,
            'house_allowance' => $housing,
            'transport_allowance' => $transport,
            'other_allowances' => $lunch,
            'overtime_hours' => $overtimeHours,
            'overtime_rate' => $overtimeRate,
            'overtime_pay' => $overtimePay,
            'gross_salary' => $grossSalary,
            
            // Legacy columns filled dynamically
            'napsa' => $napsaAmount,
            'nhima' => $nhimaAmount,
            'pension' => $pensionAmount,
            'paye' => $paye,
            
            'other_deductions' => $otherDeductions,
            'total_deductions' => $totalDeductions,
            'net_pay' => $netSalary,
            'status' => 'generated',
            'breakdown' => $breakdown,
        ]);
        
        $payslip->load('employee.user');
        
        // 10. Generate PDF
        if ($request->boolean('generate_pdf', true)) {
            try {
                $this->payslipGenerator->generatePdf($payslip);
                $payslip->refresh();
            } catch (\Exception $e) {
                Log::error('PayslipController: PDF generation failed', ['error' => $e->getMessage()]);
            }
        }
        
        return response()->json([
            'message' => 'Payslip created successfully',
            'data' => $this->formatPayslipForList($payslip)
        ], 201);
    }

    /**
     * Show single payslip details
     */
    public function show(Payslip $payslip): JsonResponse
    {
        $user = request()->user();
        
        if (!$user->isAdmin() && $payslip->employee->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $payslip->load(['employee.user', 'employee.business']);
        
        // Similar formatting to formatPayslipForList but typically detailed
        // Reusing the same structure for consistency in this example
        return response()->json([
            'data' => $this->formatPayslipForList($payslip)
        ]);
    }

    /**
     * Generate PDF for a single payslip (Adhoc)
     */
    public function generatePdf(Payslip $payslip): JsonResponse
    {
        try {
            $payslip->load(['employee.user', 'employee.business']);
            $path = $this->payslipGenerator->generatePdf($payslip);
            
            return response()->json([
                'message' => 'PDF generated successfully',
                'pdf_available' => true,
                'path' => $path
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'PDF generation failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Bulk Generate (via Payroll Run)
     */
    public function generate(GeneratePayslipRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $payroll = Payroll::findOrFail($validated['payroll_id']);
        
        if ($payroll->status !== 'completed') {
            return response()->json(['message' => 'Payroll must be completed before generating payslips'], 422);
        }
        
        $this->payslipGenerator->generateForPayroll($payroll);
        return response()->json(['message' => 'Payslip generation started']);
    }

    /**
     * Bulk Download ZIP
     */
    public function bulkDownload(Request $request): JsonResponse
    {
        $request->validate(['payroll_id' => 'required|exists:payrolls,id']);
        $payroll = Payroll::findOrFail($request->payroll_id);
        
        try {
            $zipPath = $this->payslipGenerator->bulkDownload($payroll);
            // Logic to serve file or return URL would go here
            // For now, return success
            return response()->json(['message' => 'Bulk download prepared']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Send Email Notifications
     */
    public function sendNotifications(Payslip $payslip): JsonResponse
    {
        if (!$payslip->pdf_path) {
            return response()->json(['message' => 'Payslip PDF must be generated first'], 422);
        }
        SendPayslipEmail::dispatch($payslip);
        $payslip->update(['is_sent' => true, 'status' => 'paid']);
        return response()->json(['message' => 'Payslip notification sent']);
    }

    /**
     * Download PDF File
     */
    public function download(Payslip $payslip)
    {
        $user = request()->user();
        if (!$user->isAdmin() && $payslip->employee->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        if (empty($payslip->pdf_path) || !Storage::exists($payslip->pdf_path)) {
            // Try to regenerate on the fly
            try {
                $this->payslipGenerator->generatePdf($payslip);
                $payslip->refresh();
            } catch (\Exception $e) {
                return response()->json(['message' => 'PDF not found and could not be generated.'], 404);
            }
        }
        
        $employee = $payslip->employee;
        $name = str_replace(' ', '_', $employee->user->first_name . '_' . $employee->user->last_name);
        $period = $payslip->pay_period_start ? $payslip->pay_period_start->format('Y-m') : 'payslip';
        $filename = "Payslip_{$name}_{$period}.pdf";
        
        return Storage::download($payslip->pdf_path, $filename);
    }
}