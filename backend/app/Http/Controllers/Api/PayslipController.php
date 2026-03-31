<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\PayslipGenerated;
use App\Http\Requests\Payroll\GeneratePayslipRequest;
use App\Jobs\SendPayslipEmail;
use App\Models\Payslip;
use App\Models\Payroll;
use App\Models\Employee;
use App\Services\EncryptionService;
use App\Services\PayslipGeneratorService;
use App\Models\TaxConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayslipController extends Controller
{
    protected EncryptionService $encryption;

    public function __construct(
        private PayslipGeneratorService $payslipGenerator,
        EncryptionService $encryption
    ) {
        $this->encryption = $encryption;
    }

    // =========================================================================
    // LIST ENDPOINTS
    // =========================================================================

    /**
     * Employee's own payslips.
     */
    public function index(Request $request): JsonResponse
    {
        $user     = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'message' => 'Employee record not found',
                'data'    => [],
            ], 404);
        }

        $query = Payslip::where('employee_id', $employee->id)
            ->with(['employee.user', 'employee.business'])
            ->orderBy('created_at', 'desc');

        if ($request->has('year')) {
            $query->whereYear('pay_period_start', $request->year);
        }
        if ($request->has('month')) {
            $query->whereMonth('pay_period_start', $request->month);
        }

        $payslips = $query->get();

        if ($request->has('status')) {
            $payslips = $payslips->filter(
                fn($p) => $p->status === $request->status
            )->values();
        }

        return response()->json([
            'data' => $payslips->map(fn($p) => $this->formatPayslipForList($p)),
        ]);
    }

    /**
     * Admin payslip listing with filters.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Payslip::with(['employee.user', 'employee.business']);

        if ($request->has('business_id')) {
            $query->byBusiness($request->business_id);
        } elseif ($request->user()->current_business_id) {
            $query->byBusiness($request->user()->current_business_id);
        }

        if ($request->has('pay_period')) {
            $period = $request->pay_period;
            $now    = now();
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
            try {
                $startDate = Carbon::parse($request->start)->startOfDay();
                $endDate   = Carbon::parse($request->end)->endOfDay();
                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('pay_period_start', [$startDate, $endDate])
                        ->orWhereBetween('pay_period_end', [$startDate, $endDate])
                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                            $q2->where('pay_period_start', '<=', $startDate)
                                ->where('pay_period_end', '>=', $endDate);
                        });
                });
            } catch (\Exception $e) {
                Log::error('Invalid date format in filter', [
                    'start' => $request->start,
                    'end'   => $request->end,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($request->has('department')) {
            $query->byDepartment($request->department);
        }

        $payslips = $query->orderBy('created_at', 'desc')->get();

        if ($request->has('status')) {
            $payslips = $payslips->filter(
                fn($p) => $p->status === $request->status
            )->values();
        }

        return response()->json([
            'data' => $payslips->map(fn($p) => $this->formatPayslipForList($p)),
        ]);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id'      => 'required|exists:employees,id',
            'pay_period_start' => 'required|date',
            'pay_period_end'   => 'required|date|after_or_equal:pay_period_start',
            'payment_date'     => 'required|date',
            'basic_salary'     => 'required|numeric|min:0',
            'overtime_hours'   => 'nullable|numeric|min:0',
            'overtime_rate'    => 'nullable|numeric|min:0',
            'bonuses'          => 'nullable|numeric|min:0',
            'generate_pdf'     => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $employee = Employee::with(['country', 'business'])->find($request->employee_id);
        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $businessId  = $employee->business_id;
        $countryCode = $employee->getCountryCode();

        Log::info('Creating payslip for employee', [
            'employee_id'   => $employee->id,
            'employee_name' => $employee->full_name,
            'business_id'   => $businessId,
            'country_code'  => $countryCode,
        ]);

        $taxConfig = TaxConfiguration::getForBusinessAndCountry($businessId, $countryCode);
        if (!$taxConfig) {
            return $this->buildTaxConfigErrorResponse($employee, $businessId, $countryCode);
        }

        $this->logTaxConfigUsage($employee, $taxConfig, 'manual_payslip_creation');

        $overtimeHours = (float) ($request->overtime_hours ?? 0);
        $overtimeRate  = (float) ($request->overtime_rate  ?? 0);
        $overtimePay   = $overtimeHours * $overtimeRate;
        $bonuses       = (float) ($request->bonuses        ?? 0);

        $calculation = $taxConfig->calculatePayroll($employee, $overtimePay, $bonuses);

        $legacyDeductions = $this->mapStatutoryToLegacyColumns(
            $calculation['deductions']['statutory']
        );

        $payslip = Payslip::create([
            'employee_id' => $employee->id,
            'payroll_id'  => null,
            'pay_period_start' => Carbon::parse($request->pay_period_start)->startOfDay(),
            'pay_period_end'   => Carbon::parse($request->pay_period_end)->startOfDay(),
            'payment_date'     => Carbon::parse($request->payment_date)->startOfDay(),
            'basic_salary'        => $calculation['basic_salary'],
            'gross_salary'        => $calculation['gross_salary'],
            'gross_pay'           => $calculation['gross_salary'],
            'net_pay'             => $calculation['net_salary'],
            'total_deductions'    => $calculation['deductions']['total_deductions'],
            'tax_deductions'      => $calculation['deductions']['paye_tax'],
            'house_allowance'     => $calculation['allowances']['housing'],
            'transport_allowance' => $calculation['allowances']['transport'],
            'other_allowances'    => $calculation['allowances']['lunch'],
            'overtime_rate'       => $overtimeRate,
            'overtime_pay'        => $calculation['overtime_pay'],
            'napsa'               => $legacyDeductions['napsa'],
            'nhima'               => $legacyDeductions['nhima'],
            'pension'             => $legacyDeductions['pension'],
            'paye'                => $calculation['deductions']['paye_tax'],
            'other_deductions'    => 0.0,
            'status'              => 'generated',
            'overtime_hours'      => $overtimeHours,
            'bonuses'             => $bonuses,
            'breakdown' => [
                'calculation_method'     => 'Dynamic Tax Configuration',
                'tax_config_id'          => $taxConfig->id,
                'tax_config_type'        => $this->getTaxConfigType($taxConfig),
                'tax_config_country'     => $taxConfig->country_code    ?? 'global',
                'tax_config_business_id' => $taxConfig->business_id     ?? 'all',
                'employee_country_code'  => $countryCode,
                'currency'               => $taxConfig->getCurrency(),
                'earnings_breakdown' => [
                    'basic_salary' => $calculation['basic_salary'],
                    'allowances'   => $calculation['allowances'],
                    'overtime'     => [
                        'pay'   => $calculation['overtime_pay'],
                        'hours' => $overtimeHours,
                        'rate'  => $overtimeRate,
                    ],
                    'bonuses'     => $calculation['bonuses'],
                    'gross_total' => $calculation['gross_salary'],
                ],
                'deductions_breakdown' => [
                    'paye'                => $calculation['deductions']['paye_tax'],
                    'statutory_breakdown' => $calculation['deductions']['statutory'],
                    'statutory_total'     => $calculation['deductions']['total_statutory'],
                    'other_deductions'    => 0.0,
                    'total_deductions'    => $calculation['deductions']['total_deductions'],
                ],
                'employer_costs'  => $calculation['employer_costs'],
                'net_calculation' => [
                    'gross'      => $calculation['gross_salary'],
                    'deductions' => $calculation['deductions']['total_deductions'],
                    'net'        => $calculation['net_salary'],
                ],
            ],
        ]);

        $payslip->load('employee.user');

        Log::info('Payslip created successfully', [
            'payslip_id'   => $payslip->id,
            'employee_id'  => $employee->id,
            'gross_salary' => $calculation['gross_salary'],
            'net_pay'      => $calculation['net_salary'],
        ]);

        try {
            event(new PayslipGenerated($payslip));
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch PayslipGenerated event', [
                'error' => $e->getMessage(),
            ]);
        }

        if ($request->boolean('generate_pdf', true)) {
            try {
                $this->payslipGenerator->generatePdf($payslip);
                $payslip->refresh();
            } catch (\Exception $e) {
                Log::error('PDF generation failed', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'message' => 'Payslip created successfully',
            'data'    => $this->formatPayslipForList($payslip),
        ], 201);
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function show(Payslip $payslip): JsonResponse
    {
        $user = request()->user();
        if (!$user->isAdmin() && $payslip->employee->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $payslip->load(['employee.user', 'employee.business']);

        return response()->json(['data' => $payslip->toDetailedArray()]);
    }

    // =========================================================================
    // PDF ACTIONS
    // =========================================================================

    public function generatePdf(Payslip $payslip): JsonResponse
    {
        try {
            $payslip->load(['employee.user', 'employee.business']);
            $this->payslipGenerator->generatePdf($payslip);

            return response()->json([
                'message'       => 'PDF generated successfully',
                'pdf_available' => true,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'PDF generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download the PDF for a payslip.
     *
     * ALWAYS force-regenerates the PDF before serving it so that:
     *   1. Stale/broken cached PDFs are never served.
     *   2. Admin, manager, and employee all get identical full-fidelity output
     *      from the same PayslipGeneratorService::generatePdf() call.
     *
     * FIX NOTE (frontend issue that caused admin broken PDFs):
     *   The old Vue component's downloadPayslip() had a silent catch block that
     *   fell back to a client-side jsPDF skeleton on ANY server error. That
     *   skeleton is what produced the "Basic + Net Pay only" PDF seen by admins.
     *   This backend method was always correct — the problem was purely frontend.
     *   The frontend fix (PayslipView.vue) removes the jsPDF fallback entirely and
     *   surfaces the real error instead. This backend fix adds an explicit
     *   Content-Type header so the frontend can reliably detect PDF vs JSON error.
     *
     * Template path: resources/views/pdf/payslip-template.blade.php
     * Called via:    PDF::loadView('pdf.payslip-template', ...) in PayslipGeneratorService
     */
    public function download(Payslip $payslip)
    {
        $user = request()->user();

        if (!$user->isAdmin() && $payslip->employee->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $payslip->load(['employee.user', 'employee.business']);

        // ── ALWAYS regenerate to guarantee full content ────────────────────
        // Fixes stale/broken PDFs cached from a previous failed generation.
        try {
            $this->payslipGenerator->generatePdf($payslip);

            Log::info('PDF regenerated on download', [
                'payslip_id' => $payslip->id,
                'user_id'    => $user->id,
                'is_admin'   => $user->isAdmin(),
                'user_role'  => $user->role ?? 'unknown',
            ]);
        } catch (\Exception $e) {
            Log::error('PDF regeneration failed during download', [
                'payslip_id' => $payslip->id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'PDF could not be generated: ' . $e->getMessage(),
            ], 500);
        }

        // ── Read fresh path from DB after generation ───────────────────────
        // Never trust the in-memory model after generatePdf() — it uses a
        // raw DB::table() update that bypasses Eloquent's dirty tracking.
        $pdfPath = DB::table('payslips')
            ->where('id', $payslip->id)
            ->value('pdf_path');

        Log::info('Payslip download', [
            'payslip_id' => $payslip->id,
            'pdf_path'   => $pdfPath,
            'exists'     => $pdfPath ? Storage::exists($pdfPath) : false,
        ]);

        if (empty($pdfPath) || !Storage::exists($pdfPath)) {
            return response()->json(['message' => 'PDF not found after generation.'], 404);
        }

        $employee = $payslip->employee;
        $name     = str_replace(
            ' ', '_',
            ($employee->user->first_name ?? 'unknown') . '_' . ($employee->user->last_name ?? '')
        );
        $period   = $payslip->pay_period_start
            ? $payslip->pay_period_start->format('Y-m')
            : 'payslip';
        $filename = "Payslip_{$name}_{$period}.pdf";

        // ── FIX: Explicit Content-Type header ─────────────────────────────
        // The Vue frontend checks response.headers['content-type'] to detect
        // whether it received a real PDF or a JSON error response. Without
        // this explicit header, some Laravel/proxy configurations may omit it,
        // causing the frontend to treat a PDF blob as an error.
        return Storage::download($pdfPath, $filename, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // =========================================================================
    // BULK ACTIONS
    // =========================================================================

    public function generate(GeneratePayslipRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $payroll   = Payroll::findOrFail($validated['payroll_id']);

        if ($payroll->status !== 'completed') {
            return response()->json([
                'message' => 'Payroll must be completed before generating payslips',
            ], 422);
        }

        $this->payslipGenerator->generateForPayroll($payroll);

        return response()->json(['message' => 'Payslip generation started']);
    }

    public function bulkDownload(Request $request): JsonResponse
    {
        $request->validate(['payroll_id' => 'required|exists:payrolls,id']);
        $payroll = Payroll::findOrFail($request->payroll_id);

        try {
            $zipPath = $this->payslipGenerator->bulkDownload($payroll);
            return response()->json(['message' => 'Bulk download prepared', 'path' => $zipPath]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function sendNotifications(Payslip $payslip): JsonResponse
    {
        if (!$payslip->pdf_path) {
            return response()->json([
                'message' => 'Payslip PDF must be generated first',
            ], 422);
        }

        SendPayslipEmail::dispatch($payslip);
        $payslip->update(['is_sent' => true, 'status' => 'paid']);

        return response()->json(['message' => 'Payslip notification sent']);
    }

    // =========================================================================
    // PRIVATE FORMATTERS
    // =========================================================================

    private function formatPayslipForList(Payslip $payslip): array
    {
        $period = 'N/A';
        if ($payslip->pay_period_start) {
            $period = $payslip->pay_period_start->format('M Y');
        } elseif ($payslip->created_at) {
            $period = $payslip->created_at->format('M Y');
        }

        // Read raw path directly from DB attributes — bypasses encryption and
        // Eloquent model hooks — so this is reliable for all roles.
        $rawPdfPath = $payslip->attributes['pdf_path'] ?? null;

        // FIX: Check that the file actually exists on disk, not just that the
        // DB column is non-empty. A non-empty path pointing to a deleted/missing
        // file would cause the Vue download to fail unexpectedly.
        $pdfAvailable = !empty($rawPdfPath) && Storage::exists($rawPdfPath);

        return [
            'id'               => $payslip->id,
            'employee_id'      => $payslip->employee->employee_id ?? 'N/A',
            'employee_name'    => $payslip->employee->full_name   ?? 'N/A',
            'department'       => $payslip->employee->department  ?? 'N/A',
            'business_name'    => $payslip->employee->business->name ?? 'N/A',
            'period'           => $period,
            'pay_period_start' => $payslip->pay_period_start?->format('Y-m-d'),
            'pay_period_end'   => $payslip->pay_period_end?->format('Y-m-d'),
            'payment_date'     => $payslip->payment_date?->format('Y-m-d'),
            'basic_salary'     => (float) ($payslip->basic_salary     ?? 0),
            'gross_salary'     => (float) ($payslip->gross_salary     ?? 0),
            'net_pay'          => (float) ($payslip->net_pay          ?? 0),
            'total_deductions' => (float) ($payslip->total_deductions ?? 0),
            'currency'         => $payslip->currency,
            'status'           => $payslip->status ?? 'generated',
            'is_sent'          => (bool) ($payslip->is_sent ?? false),
            // NOTE: The Vue download function no longer relies on this flag to
            // decide whether to call the server — it always calls the server.
            // This flag is kept for UI purposes only (e.g., showing a "PDF ready" indicator).
            'pdf_available'    => $pdfAvailable,
            'uses_dynamic_config' => $payslip->uses_dynamic_tax_config,
        ];
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function mapStatutoryToLegacyColumns(array $statutory): array
    {
        $col = collect($statutory);

        $napsa = $col->where('type', 'levy')
            ->filter(fn($i) => stripos($i['name'], 'NAPSA') !== false)
            ->sum('amount');

        $nhima   = $col->where('type', 'health')->sum('amount');
        $pension = $col->where('type', 'pension')
            ->filter(fn($i) => stripos($i['name'], 'NAPSA') === false)
            ->sum('amount');

        return [
            'napsa'   => round($napsa,   2),
            'nhima'   => round($nhima,   2),
            'pension' => round($pension, 2),
        ];
    }

    private function getTaxConfigType(TaxConfiguration $taxConfig): string
    {
        if ($taxConfig->business_id && $taxConfig->country_code) {
            return 'business-specific';
        }
        if ($taxConfig->country_code) {
            return 'country-specific';
        }
        return 'global';
    }

    private function logTaxConfigUsage(
        Employee $employee,
        TaxConfiguration $taxConfig,
        string $operation
    ): void {
        Log::info('Tax configuration applied', [
            'operation'              => $operation,
            'employee_id'            => $employee->id,
            'employee_name'          => trim(
                ($employee->user->first_name ?? '') . ' ' . ($employee->user->last_name ?? '')
            ),
            'business_id'            => $employee->business_id,
            'business_name'          => $employee->business->name ?? 'Unknown',
            'employee_country_code'  => $employee->getCountryCode(),
            'tax_config_id'          => $taxConfig->id,
            'tax_config_type'        => $this->getTaxConfigType($taxConfig),
            'tax_config_country'     => $taxConfig->country_code ?? 'global',
            'tax_config_business_id' => $taxConfig->business_id  ?? 'all',
            'currency'               => $taxConfig->getCurrency(),
        ]);
    }

    private function buildTaxConfigErrorResponse(
        Employee $employee,
        ?int $businessId,
        ?string $countryCode
    ): JsonResponse {
        $errorMessage = 'No active tax configuration found.';
        $suggestions  = [];

        if (!$countryCode) {
            $errorMessage .= ' The employee has no country assigned.';
            $suggestions[] = 'Please assign a country to the employee or their business.';
        }
        if ($businessId) {
            $suggestions[] = 'Create a business-specific tax configuration for business ID: ' . $businessId;
        }
        if ($countryCode) {
            $suggestions[] = 'Create a country-specific tax configuration for country: ' . $countryCode;
        }
        $suggestions[] = 'Create a global tax configuration as a fallback.';

        Log::error('No tax configuration found', [
            'employee_id'  => $employee->id,
            'business_id'  => $businessId,
            'country_code' => $countryCode,
        ]);

        return response()->json([
            'message'     => $errorMessage,
            'suggestions' => $suggestions,
            'details'     => [
                'business_id'   => $businessId,
                'business_name' => $employee->business->name ?? 'Unknown',
                'country_code'  => $countryCode ?? 'Not set',
            ],
        ], 422);
    }
}