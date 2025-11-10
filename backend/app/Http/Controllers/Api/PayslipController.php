<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\GeneratePayslipRequest;
use App\Http\Resources\PayslipResource;
use App\Jobs\GeneratePayslipPdf;
use App\Jobs\SendPayslipEmail;
use App\Models\Payslip;
use App\Models\Payroll;
use App\Models\Employee;
use App\Services\PayslipGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PayslipController extends Controller
{
    public function __construct(private PayslipGeneratorService $payslipGenerator) {}

    /**
     * List payslips for employees (filtered by current user if employee)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get employee record
        $employee = $user->employee;
        
        if (!$employee) {
            return response()->json([
                'message' => 'Employee record not found',
                'data' => []
            ], 404);
        }

        $query = Payslip::where('employee_id', $employee->id)
            ->with(['employee.user'])
            ->orderBy('created_at', 'desc'); // Changed from pay_period_start to created_at

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
            // Determine period display
            $period = 'N/A';
            if ($payslip->pay_period_start) {
                $period = $payslip->pay_period_start->format('M Y');
            } elseif ($payslip->created_at) {
                $period = $payslip->created_at->format('M Y');
            }

            // Check if PDF exists and is accessible
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
                'house_allowance' => (float) ($payslip->house_allowance ?? 0),
                'transport_allowance' => (float) ($payslip->transport_allowance ?? 0),
                'other_allowances' => (float) ($payslip->other_allowances ?? 0),
                'overtime_hours' => (float) ($payslip->overtime_hours ?? 0),
                'overtime_rate' => (float) ($payslip->overtime_rate ?? 0),
                'overtime_pay' => (float) ($payslip->overtime_pay ?? 0),
                'napsa' => (float) ($payslip->napsa ?? 0),
                'paye' => (float) ($payslip->paye ?? 0),
                'nhima' => (float) ($payslip->nhima ?? 0),
                'other_deductions' => (float) ($payslip->other_deductions ?? 0),
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
                'basic_salary' => $payslip->basic_salary,
                'house_allowance' => $payslip->house_allowance ?? 0,
                'transport_allowance' => $payslip->transport_allowance ?? 0,
                'other_allowances' => $payslip->other_allowances ?? 0,
                'overtime_hours' => $payslip->overtime_hours ?? 0,
                'overtime_rate' => $payslip->overtime_rate ?? 0,
                'napsa' => $payslip->napsa ?? 0,
                'paye' => $payslip->paye ?? 0,
                'nhima' => $payslip->nhima ?? 0,
                'other_deductions' => $payslip->other_deductions ?? 0,
                'gross_salary' => $payslip->gross_salary,
                'total_deductions' => $payslip->total_deductions,
                'net_pay' => $payslip->net_pay,
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
     * Create a new payslip directly (without payroll requirement) and optionally generate PDF
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'pay_period_start' => 'nullable|date',
            'pay_period_end' => 'nullable|date|after:pay_period_start',
            'payment_date' => 'nullable|date',
            'basic_salary' => 'required|numeric|min:0',
            'house_allowance' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'other_allowances' => 'nullable|numeric|min:0',
            'overtime_hours' => 'nullable|numeric|min:0',
            'overtime_rate' => 'nullable|numeric|min:0',
            'napsa' => 'nullable|numeric|min:0',
            'paye' => 'nullable|numeric|min:0',
            'nhima' => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|numeric|min:0',
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

        // Calculate derived fields
        $overtimePay = ($request->overtime_hours ?? 0) * ($request->overtime_rate ?? 0);
        $grossSalary = $request->basic_salary 
            + ($request->house_allowance ?? 0) 
            + ($request->transport_allowance ?? 0) 
            + ($request->other_allowances ?? 0) 
            + $overtimePay;
        $totalDeductions = ($request->napsa ?? 0) 
            + ($request->paye ?? 0) 
            + ($request->nhima ?? 0) 
            + ($request->other_deductions ?? 0);
        $netPay = $grossSalary - $totalDeductions;

        // Create payslip
        $payslip = Payslip::create([
            'employee_id' => $employee->id,
            'payroll_id' => null, // Make sure this is explicitly null
            'pay_period_start' => $request->pay_period_start,
            'pay_period_end' => $request->pay_period_end,
            'payment_date' => $request->payment_date ?? now(),
            'basic_salary' => $request->basic_salary,
            'house_allowance' => $request->house_allowance ?? 0,
            'transport_allowance' => $request->transport_allowance ?? 0,
            'other_allowances' => $request->other_allowances ?? 0,
            'overtime_hours' => $request->overtime_hours ?? 0,
            'overtime_rate' => $request->overtime_rate ?? 0,
            'overtime_pay' => $overtimePay,
            'gross_salary' => $grossSalary,
            'napsa' => $request->napsa ?? 0,
            'paye' => $request->paye ?? 0,
            'nhima' => $request->nhima ?? 0,
            'other_deductions' => $request->other_deductions ?? 0,
            'total_deductions' => $totalDeductions,
            'net_pay' => $netPay,
            'status' => 'generated',
        ]);

        $payslip->load('employee.user');

        // Always generate PDF by default
        if ($request->boolean('generate_pdf', true)) {
            try {
                $this->payslipGenerator->generatePdf($payslip);
                $payslip->refresh(); // Reload to get updated pdf_path
            } catch (\Exception $e) {
                \Log::error('PDF generation failed: ' . $e->getMessage());
            }
        }

        // Format response
        $formatted = [
            'id' => $payslip->id,
            'employee_id' => $payslip->employee->employee_id ?? 'N/A',
            'employee_name' => $payslip->employee->user->first_name . ' ' . $payslip->employee->user->last_name,
            'department' => $payslip->employee->department ?? 'N/A',
            'pay_period_start' => $payslip->pay_period_start?->format('Y-m-d'),
            'pay_period_end' => $payslip->pay_period_end?->format('Y-m-d'),
            'payment_date' => $payslip->payment_date?->format('Y-m-d'),
            'basic_salary' => $payslip->basic_salary,
            'house_allowance' => $payslip->house_allowance,
            'transport_allowance' => $payslip->transport_allowance,
            'other_allowances' => $payslip->other_allowances,
            'overtime_hours' => $payslip->overtime_hours,
            'overtime_rate' => $payslip->overtime_rate,
            'napsa' => $payslip->napsa,
            'paye' => $payslip->paye,
            'nhima' => $payslip->nhima,
            'other_deductions' => $payslip->other_deductions,
            'gross_salary' => $payslip->gross_salary,
            'total_deductions' => $payslip->total_deductions,
            'net_pay' => $payslip->net_pay,
            'status' => $payslip->status,
            'pdf_available' => !empty($payslip->pdf_path) && Storage::exists($payslip->pdf_path),
            'created_at' => $payslip->created_at,
        ];

        return response()->json([
            'message' => 'Payslip created successfully',
            'data' => $formatted
        ], 201);
    }

  
    /**
     * Download payslip PDF - Auto-generate if missing
     */
    public function download(Payslip $payslip): JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        \Log::info("Download request received for payslip ID: {$payslip->id}");
        
        // Check if user has permission to download this payslip
        $user = request()->user();
        if (!$user->isAdmin() && $payslip->employee->user_id !== $user->id) {
            \Log::warning("Unauthorized download attempt for payslip {$payslip->id} by user {$user->id}");
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        \Log::info("User authorized. Current PDF path: " . ($payslip->pdf_path ?? 'null'));
        
        // Auto-generate PDF if it doesn't exist
        if (!$payslip->pdf_path || !Storage::exists($payslip->pdf_path)) {
            try {
                \Log::info("PDF not found. Generating PDF for payslip {$payslip->id}");
                $this->payslipGenerator->generatePdf($payslip);
                $payslip->refresh();
                \Log::info("PDF generated successfully. New path: {$payslip->pdf_path}");
            } catch (\Exception $e) {
                \Log::error("PDF generation failed for payslip {$payslip->id}: " . $e->getMessage());
                \Log::error("Stack trace: " . $e->getTraceAsString());
                return response()->json([
                    'message' => 'Failed to generate payslip PDF: ' . $e->getMessage()
                ], 500);
            }
        }

        // Double check PDF exists after generation attempt
        if (!$payslip->pdf_path || !Storage::exists($payslip->pdf_path)) {
            \Log::error("PDF still not available after generation attempt for payslip {$payslip->id}");
            return response()->json([
                'message' => 'Payslip PDF could not be generated'
            ], 500);
        }

        $period = $payslip->pay_period_start 
            ? $payslip->pay_period_start->format('Y-m') 
            : $payslip->created_at->format('Y-m');

        $fullPath = Storage::path($payslip->pdf_path);
        \Log::info("Serving PDF file: {$fullPath}");
        
        if (!file_exists($fullPath)) {
            \Log::error("PDF file does not exist at path: {$fullPath}");
            return response()->json([
                'message' => 'PDF file not found on disk'
            ], 404);
        }

        return response()->download(
            $fullPath,
            "payslip-{$payslip->employee->employee_id}-{$period}.pdf",
            [
                'Content-Type' => 'application/pdf',
            ]
        );
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
            'basic_salary' => $payslip->basic_salary,
            'house_allowance' => $payslip->house_allowance ?? 0,
            'transport_allowance' => $payslip->transport_allowance ?? 0,
            'other_allowances' => $payslip->other_allowances ?? 0,
            'overtime_hours' => $payslip->overtime_hours ?? 0,
            'overtime_rate' => $payslip->overtime_rate ?? 0,
            'overtime_pay' => $payslip->overtime_pay ?? 0,
            'napsa' => $payslip->napsa ?? 0,
            'paye' => $payslip->paye ?? 0,
            'nhima' => $payslip->nhima ?? 0,
            'other_deductions' => $payslip->other_deductions ?? 0,
            'gross_salary' => $payslip->gross_salary,
            'total_deductions' => $payslip->total_deductions,
            'net_pay' => $payslip->net_pay,
            'status' => $payslip->status,
            'pdf_available' => !empty($payslip->pdf_path) && Storage::exists($payslip->pdf_path),
        ];

        return response()->json([
            'data' => $formatted
        ]);
    }

    // Generate PDF for a single payslip
    public function generatePdf(Payslip $payslip): JsonResponse
    {
        try {
            $this->payslipGenerator->generatePdf($payslip);
            return response()->json([
                'message' => 'PDF generated successfully',
                'pdf_available' => true
            ]);
        } catch (\Exception $e) {
            \Log::error('PDF generation failed: ' . $e->getMessage());
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
}