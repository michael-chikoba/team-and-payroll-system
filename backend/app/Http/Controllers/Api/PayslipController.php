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
            ->with(['employee.user'])
            ->orderBy('created_at', 'desc');
        // Apply filters if provided
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
        // Format the response for employee view
        $formatted = $payslips->map(function ($payslip) {
            $period = 'N/A';
            if ($payslip->pay_period_start) {
                $period = $payslip->pay_period_start->format('M Y');
            } elseif ($payslip->created_at) {
                $period = $payslip->created_at->format('M Y');
            }
            $pdfAvailable = !empty($payslip->pdf_path) && Storage::exists($payslip->pdf_path);
            return [
                'id' => $payslip->id,
                'period' => $period,
                'pay_period_start' => $payslip->pay_period_start?->format('Y-m-d') ?? $payslip->created_at->format('Y-m-d'),
                'pay_period_end' => $payslip->pay_period_end?->format('Y-m-d') ?? $payslip->created_at->format('Y-m-d'),
                'grossPay' => (float) $payslip->gross_salary,
                'deductions' => (float) $payslip->total_deductions,
                'netPay' => (float) $payslip->net_pay,
                'status' => $payslip->status ?? 'generated',
                'generatedDate' => $payslip->created_at->format('Y-m-d'),
                'payment_date' => $payslip->payment_date?->format('Y-m-d') ?? $payslip->created_at->format('Y-m-d'),
                'basic_salary' => (float) $payslip->basic_salary,
                'house_allowance' => (float) $payslip->house_allowance,
                'transport_allowance' => (float) $payslip->transport_allowance,
                'lunch_allowance' => (float) $payslip->lunch_allowance,
                'overtime_hours' => (float) $payslip->overtime_hours,
                'overtime_rate' => (float) $payslip->overtime_rate,
                'overtime_pay' => (float) $payslip->overtime_pay,
                'napsa' => (float) $payslip->napsa,
                'paye' => (float) $payslip->paye,
                'nhima' => (float) $payslip->nhima,
                'other_deductions' => (float) $payslip->other_deductions,
                'pdf_available' => $pdfAvailable,
                'pdf_path' => $payslip->pdf_path,
            ];
        });
        return response()->json([
            'data' => $formatted
        ]);
    }
    /**
     * List all payslips for admin with filters
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Payslip::with(['employee.user']);
        // Apply filters
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
        // Format the response
        $formatted = $payslips->map(function ($payslip) {
            return [
                'id' => $payslip->id,
                'employee_id' => $payslip->employee->employee_id ?? 'N/A',
                'employee_name' => $payslip->employee->user->first_name . ' ' . $payslip->employee->user->last_name,
                'department' => $payslip->employee->department ?? 'N/A',
                'pay_period_start' => $payslip->pay_period_start?->format('Y-m-d'),
                'pay_period_end' => $payslip->pay_period_end?->format('Y-m-d'),
                'payment_date' => $payslip->payment_date?->format('Y-m-d'),
                'basic_salary' => (float) $payslip->basic_salary,
                'house_allowance' => (float) $payslip->house_allowance,
                'transport_allowance' => (float) $payslip->transport_allowance,
                'lunch_allowance' => (float) $payslip->lunch_allowance,
                'overtime_hours' => (float) $payslip->overtime_hours,
                'overtime_rate' => (float) $payslip->overtime_rate,
                'overtime_pay' => (float) $payslip->overtime_pay,
                'napsa' => (float) $payslip->napsa,
                'paye' => (float) $payslip->paye,
                'nhima' => (float) $payslip->nhima,
                'other_deductions' => (float) $payslip->other_deductions,
                'gross_salary' => (float) $payslip->gross_salary,
                'total_deductions' => (float) $payslip->total_deductions,
                'net_pay' => (float) $payslip->net_pay,
                'status' => $payslip->status ?? 'draft',
                'created_at' => $payslip->created_at,
                'is_sent' => $payslip->is_sent ?? false,
                'pdf_available' => !empty($payslip->pdf_path) && Storage::exists($payslip->pdf_path),
            ];
        });
        return response()->json([
            'data' => $formatted
        ]);
    }
    /**
     * Create a new payslip with proper tax configuration-based calculations
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'pay_period_start' => 'required|date',
            'pay_period_end' => 'required|date|after:pay_period_start',
            'payment_date' => 'required|date',
            'basic_salary' => 'required|numeric|min:0',
            'overtime_hours' => 'nullable|numeric|min:0',
            'overtime_rate' => 'nullable|numeric|min:0',
            'generate_pdf' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        // Find employee by ID
        $employee = Employee::find($request->employee_id);
        if (!$employee) {
            return response()->json([
                'message' => 'Employee not found'
            ], 404);
        }
        // Get active tax configuration for Zambia
        $taxConfig = TaxConfiguration::forLocation('Zambia')->first();
        if (!$taxConfig) {
            return response()->json([
                'message' => 'No active tax configuration found for Zambia'
            ], 500);
        }
        $basicSalary = $request->basic_salary ?? $employee->base_salary ?? 0;
        
        if ($basicSalary <= 0) {
            return response()->json([
                'message' => 'Basic salary must be greater than 0'
            ], 422);
        }
        // Calculate overtime pay
        $overtimePay = ($request->overtime_hours ?? 0) * ($request->overtime_rate ?? 0);
        $bonuses = 0.0;
        // Calculate complete payroll using tax configuration
        $payrollCalculation = $taxConfig->calculatePayroll($employee, $overtimePay, $bonuses);
        // Create detailed breakdown for payslip
        $breakdown = [
            'basic_salary' => $basicSalary,
            'housing_allowance' => $payrollCalculation['allowances']['housing'],
            'transport_allowance' => $payrollCalculation['allowances']['transport'],
            'lunch_allowance' => $payrollCalculation['allowances']['lunch'],
            'total_allowances' => $payrollCalculation['allowances']['total'],
            'overtime_pay' => $overtimePay,
            'bonuses' => $bonuses,
            'gross_salary' => $payrollCalculation['gross_salary'],
            'paye_tax' => $payrollCalculation['deductions']['paye_tax'],
            'napsa_deduction' => $payrollCalculation['deductions']['napsa_deduction'],
            'nhima_deduction' => $payrollCalculation['deductions']['nhima_deduction'],
            'total_deductions' => $payrollCalculation['deductions']['total_deductions'],
            'net_salary' => $payrollCalculation['net_salary'],
            'calculation_notes' => $payrollCalculation['calculation_notes'],
            'tax_config_used' => [
                'housing_rate' => '25%',
                'napsa_rate' => '5% of gross (capped)',
                'nhima_rate' => '1% of gross',
                'paye_base' => 'Basic salary only'
            ]
        ];
        // Ensure dates are stored as date-only (no time component)
        $payPeriodStart = Carbon::parse($request->pay_period_start)->startOfDay();
        $payPeriodEnd = Carbon::parse($request->pay_period_end)->startOfDay();
        $paymentDate = Carbon::parse($request->payment_date)->startOfDay();
        Log::info('PayslipController: Creating new payslip', [
            'employee_id' => $employee->id,
            'employee_name' => $employee->user->first_name . ' ' . $employee->user->last_name,
            'pay_period_start' => $payPeriodStart->format('Y-m-d'),
            'pay_period_end' => $payPeriodEnd->format('Y-m-d'),
            'payment_date' => $paymentDate->format('Y-m-d'),
            'basic_salary' => $basicSalary,
            'gross_salary' => $payrollCalculation['gross_salary'],
            'total_deductions' => $payrollCalculation['deductions']['total_deductions'],
            'net_pay' => $payrollCalculation['net_salary'],
            'breakdown_summary' => [
                'allowances' => $payrollCalculation['allowances'],
                'deductions' => $payrollCalculation['deductions'],
            ],
        ]);
        // Create payslip record
        $payslip = Payslip::create([
            'employee_id' => $employee->id,
            'payroll_id' => null,
            'pay_period_start' => $payPeriodStart,
            'pay_period_end' => $payPeriodEnd,
            'payment_date' => $paymentDate,
            'basic_salary' => $basicSalary,
            'house_allowance' => $payrollCalculation['allowances']['housing'],
            'transport_allowance' => $payrollCalculation['allowances']['transport'],
            'other_allowances' => $payrollCalculation['allowances']['lunch'],
            'overtime_hours' => $request->overtime_hours ?? 0,
            'overtime_rate' => $request->overtime_rate ?? 0,
            'overtime_pay' => $overtimePay,
            'gross_salary' => $payrollCalculation['gross_salary'],
            'napsa' => $payrollCalculation['deductions']['napsa_deduction'],
            'paye' => $payrollCalculation['deductions']['paye_tax'],
            'nhima' => $payrollCalculation['deductions']['nhima_deduction'],
            'other_deductions' => 0,
            'total_deductions' => $payrollCalculation['deductions']['total_deductions'],
            'net_pay' => $payrollCalculation['net_salary'],
            'status' => 'generated',
            'breakdown' => $breakdown,
        ]);
        $payslip->load('employee.user');
        Log::info('PayslipController: Payslip created successfully', [
            'payslip_id' => $payslip->id,
            'employee_id' => $employee->id,
        ]);
        // Generate PDF if requested
        if ($request->boolean('generate_pdf', true)) {
            try {
                $this->payslipGenerator->generatePdf($payslip);
                $payslip->refresh();
            } catch (\Exception $e) {
                Log::error('PayslipController: PDF generation failed after payslip creation', [
                    'payslip_id' => $payslip->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        // Format response
        $formatted = $this->formatPayslipResponse($payslip);
        return response()->json([
            'message' => 'Payslip created successfully',
            'data' => $formatted
        ], 201);
    }
    /**
     * Format payslip for API response
     */
    private function formatPayslipResponse(Payslip $payslip): array
    {
        $breakdown = $payslip->breakdown ?? [];
        
        return [
            'id' => $payslip->id,
            'employee_id' => $payslip->employee->employee_id ?? 'N/A',
            'employee_name' => $payslip->employee->user->first_name . ' ' . $payslip->employee->user->last_name,
            'department' => $payslip->employee->department ?? 'N/A',
            'pay_period_start' => $payslip->pay_period_start?->format('Y-m-d'),
            'pay_period_end' => $payslip->pay_period_end?->format('Y-m-d'),
            'payment_date' => $payslip->payment_date?->format('Y-m-d'),
            'basic_salary' => (float) $payslip->basic_salary,
            'house_allowance' => (float) $payslip->house_allowance,
            'transport_allowance' => (float) $payslip->transport_allowance,
            'lunch_allowance' => (float) $payslip->lunch_allowance,
            'overtime_hours' => (float) $payslip->overtime_hours,
            'overtime_rate' => (float) $payslip->overtime_rate,
            'overtime_pay' => (float) $payslip->overtime_pay,
            'napsa' => (float) $payslip->napsa,
            'paye' => (float) $payslip->paye,
            'nhima' => (float) $payslip->nhima,
            'other_deductions' => (float) $payslip->other_deductions,
            'gross_salary' => (float) $payslip->gross_salary,
            'total_deductions' => (float) $payslip->total_deductions,
            'net_pay' => (float) $payslip->net_pay,
            'status' => $payslip->status,
            'pdf_available' => !empty($payslip->pdf_path) && Storage::exists($payslip->pdf_path),
            'breakdown' => $breakdown,
        ];
    }
    public function show(Payslip $payslip): JsonResponse
    {
        $user = request()->user();
        
        // Check authorization
        if (!$user->isAdmin() && $payslip->employee->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        $payslip->load(['employee.user']);
        
        $formatted = [
            'id' => $payslip->id,
            'employee_id' => $payslip->employee->employee_id ?? 'N/A',
            'employee_name' => $payslip->employee->user->first_name . ' ' . $payslip->employee->user->last_name,
            'department' => $payslip->employee->department ?? 'N/A',
            'pay_period_start' => $payslip->pay_period_start?->format('Y-m-d'),
            'pay_period_end' => $payslip->pay_period_end?->format('Y-m-d'),
            'payment_date' => $payslip->payment_date?->format('Y-m-d'),
            'basic_salary' => (float) $payslip->basic_salary,
            'house_allowance' => (float) $payslip->house_allowance,
            'transport_allowance' => (float) $payslip->transport_allowance,
            'lunch_allowance' => (float) $payslip->lunch_allowance,
            'overtime_hours' => (float) $payslip->overtime_hours,
            'overtime_rate' => (float) $payslip->overtime_rate,
            'overtime_pay' => (float) $payslip->overtime_pay,
            'napsa' => (float) $payslip->napsa,
            'paye' => (float) $payslip->paye,
            'nhima' => (float) $payslip->nhima,
            'other_deductions' => (float) $payslip->other_deductions,
            'gross_salary' => (float) $payslip->gross_salary,
            'total_deductions' => (float) $payslip->total_deductions,
            'net_pay' => (float) $payslip->net_pay,
            'status' => $payslip->status,
            'pdf_available' => !empty($payslip->pdf_path) && Storage::exists($payslip->pdf_path),
            'breakdown' => $payslip->breakdown ?? [],
        ];
        return response()->json([
            'data' => $formatted
        ]);
    }
    /**
     * Generate PDF for a single payslip
     */
    public function generatePdf(Payslip $payslip): JsonResponse
    {
        try {
            $payslip->load(['employee.user']);
            
            Log::info('PayslipController: PDF generation requested', [
                'payslip_id' => $payslip->id,
                'employee_id' => $payslip->employee->id,
                'employee_name' => $payslip->employee->user->first_name . ' ' . $payslip->employee->user->last_name,
                'user_id' => request()->user()->id,
            ]);
            
            $this->payslipGenerator->generatePdf($payslip);
            
            return response()->json([
                'message' => 'PDF generated successfully',
                'pdf_available' => true
            ]);
        } catch (\Exception $e) {
            Log::error('PayslipController: PDF generation failed', [
                'payslip_id' => $payslip->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'PDF generation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function generate(GeneratePayslipRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $payroll = Payroll::findOrFail($validated['payroll_id']);
        if ($payroll->status !== 'completed') {
            return response()->json([
                'message' => 'Payroll must be completed before generating payslips'
            ], 422);
        }
        $this->payslipGenerator->generateForPayroll($payroll);
        return response()->json([
            'message' => 'Payslip generation started'
        ]);
    }
    public function bulkDownload(Request $request): JsonResponse
    {
        $request->validate([
            'payroll_id' => 'required|exists:payrolls,id',
        ]);
        $payroll = Payroll::findOrFail($request->payroll_id);
        try {
            $zipPath = $this->payslipGenerator->bulkDownload($payroll);
            
            return response()->json([
                'download_url' => url("api/payrolls/{$payroll->id}/bulk-download")
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function sendNotifications(Payslip $payslip): JsonResponse
    {
        if (!$payslip->pdf_path) {
            return response()->json([
                'message' => 'Payslip PDF must be generated before sending notifications'
            ], 422);
        }
        SendPayslipEmail::dispatch($payslip);
        $payslip->update(['is_sent' => true, 'status' => 'paid']);
        return response()->json([
            'message' => 'Payslip notification sent'
        ]);
    }
    /**
     * Download payslip PDF
     */
    public function download(Payslip $payslip)
    {
        $user = request()->user();
        
        // Check authorization
        if (!$user->isAdmin() && $payslip->employee->user_id !== $user->id) {
            Log::warning('PayslipController: Unauthorized download attempt', [
                'payslip_id' => $payslip->id,
                'attempted_by_user_id' => $user->id,
                'payslip_owner_user_id' => $payslip->employee->user_id,
            ]);
            
            return response()->json([
                'message' => 'Unauthorized to download this payslip'
            ], 403);
        }
        
        // Check if PDF exists
        if (empty($payslip->pdf_path) || !Storage::exists($payslip->pdf_path)) {
            Log::error('PayslipController: PDF not found for download', [
                'payslip_id' => $payslip->id,
                'pdf_path' => $payslip->pdf_path,
                'user_id' => $user->id,
            ]);
            
            return response()->json([
                'message' => 'Payslip PDF not found. Please generate the PDF first.'
            ], 404);
        }
        
        $payslip->load(['employee.user']);
        
        $employee = $payslip->employee;
        $employeeName = str_replace(' ', '_', $employee->user->first_name . '_' . $employee->user->last_name);
        $period = $payslip->pay_period_start ? $payslip->pay_period_start->format('Y-m') : $payslip->created_at->format('Y-m');
        $filename = "Payslip_{$employeeName}_{$period}.pdf";
        
        Log::info('PayslipController: Downloading payslip PDF', [
            'payslip_id' => $payslip->id,
            'employee_id' => $employee->id,
            'employee_name' => $employee->user->first_name . ' ' . $employee->user->last_name,
            'filename' => $filename,
            'downloaded_by_user_id' => $user->id,
            'downloaded_by_name' => $user->first_name . ' ' . $user->last_name,
            'is_admin' => $user->isAdmin(),
            'timestamp' => now()->toDateTimeString(),
        ]);
        
        try {
            return Storage::download($payslip->pdf_path, $filename);
        } catch (\Exception $e) {
            Log::error('PayslipController: Download failed', [
                'payslip_id' => $payslip->id,
                'pdf_path' => $payslip->pdf_path,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Failed to download payslip: ' . $e->getMessage()
            ], 500);
        }
    }
}