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

    /**
     * List payslips for employees - encrypted fields auto-handled by trait
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
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $payslips  = $query->get();
        $formatted = $payslips->map(fn($p) => $this->formatPayslipForList($p));

        return response()->json(['data' => $formatted]);
    }

    /**
     * List all payslips for admin with filters - encrypted fields auto-handled by trait
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Payslip::with(['employee.user', 'employee.business']);

        // Filter by Business
        if ($request->has('business_id')) {
            $query->byBusiness($request->business_id);
        } elseif ($request->user()->current_business_id) {
            $query->byBusiness($request->user()->current_business_id);
        }

        // Filter by Pay Period
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

        // Custom Date Range Filter
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
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        $payslips  = $query->orderBy('created_at', 'desc')->get();
        $formatted = $payslips->map(fn($p) => $this->formatPayslipForList($p));

        return response()->json(['data' => $formatted]);
    }

    /**
     * Create a new payslip - encrypted fields auto-handled by HasEncryptedFields trait
     */
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

        // Load Employee with relationships needed for tax calculations
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

        // Resolve Tax Configuration (business-specific → country-specific → global)
        $taxConfig = TaxConfiguration::getForBusinessAndCountry($businessId, $countryCode);

        if (!$taxConfig) {
            return $this->buildTaxConfigErrorResponse($employee, $businessId, $countryCode);
        }

        $this->logTaxConfigUsage($employee, $taxConfig, 'manual_payslip_creation');

        // Run payroll calculation via TaxConfiguration service methods
        $overtimeHours = (float) ($request->overtime_hours ?? 0);
        $overtimeRate  = (float) ($request->overtime_rate ?? 0);
        $overtimePay   = $overtimeHours * $overtimeRate;
        $bonuses       = (float) ($request->bonuses ?? 0);

        $calculation = $taxConfig->calculatePayroll($employee, $overtimePay, $bonuses);

        // Map statutory deductions to legacy columns (backward compat)
        $legacyDeductions = $this->mapStatutoryToLegacyColumns(
            $calculation['deductions']['statutory']
        );

        // ──────────────────────────────────────────────────────────────────────
        // Payslip::create() will pass all financial fields through the
        // HasEncryptedFields trait automatically — no manual encryption needed.
        // The trait intercepts setAttribute() for every field listed in
        // getEncryptedFields() and encrypts before persisting.
        // ──────────────────────────────────────────────────────────────────────
        $payslip = Payslip::create([
            'employee_id' => $employee->id,
            'payroll_id'  => null,

            // Dates
            'pay_period_start' => Carbon::parse($request->pay_period_start)->startOfDay(),
            'pay_period_end'   => Carbon::parse($request->pay_period_end)->startOfDay(),
            'payment_date'     => Carbon::parse($request->payment_date)->startOfDay(),

            // Encrypted financial fields (trait handles encryption transparently)
            'basic_salary'     => $calculation['basic_salary'],
            'gross_salary'     => $calculation['gross_salary'],
            'net_pay'          => $calculation['net_salary'],
            'total_deductions' => $calculation['deductions']['total_deductions'],
            'tax_deductions'   => $calculation['deductions']['paye_tax'],
            'house_allowance'  => $calculation['allowances']['housing'],
            'transport_allowance' => $calculation['allowances']['transport'],
            'other_allowances' => $calculation['allowances']['lunch'],
            'overtime_rate'    => $overtimeRate,
            'overtime_pay'     => $calculation['overtime_pay'],
            'gross_pay'        => $calculation['gross_salary'],

            // These are also in getEncryptedFields()
            'napsa'            => $legacyDeductions['napsa'],
            'nhima'            => $legacyDeductions['nhima'],
            'pension'          => $legacyDeductions['pension'],
            'paye'             => $calculation['deductions']['paye_tax'],
            'other_deductions' => 0.0,

            // Plain (unencrypted) fields
            'overtime_hours'   => $overtimeHours,
            'bonuses'          => $bonuses,
            'status'           => 'generated',

            // Breakdown JSON — stores decrypted values for audit / PDF rendering.
            // These are never queried for payroll maths; they exist for display only.
            'breakdown' => [
                'calculation_method'    => 'Dynamic Tax Configuration',
                'tax_config_id'         => $taxConfig->id,
                'tax_config_type'       => $this->getTaxConfigType($taxConfig),
                'tax_config_country'    => $taxConfig->country_code ?? 'global',
                'tax_config_business_id'=> $taxConfig->business_id ?? 'all',
                'employee_country_code' => $countryCode,
                'currency'              => $taxConfig->getCurrency(),
                'earnings_breakdown'    => [
                    'basic_salary' => $calculation['basic_salary'],
                    'allowances'   => $calculation['allowances'],
                    'overtime'     => [
                        'pay'   => $calculation['overtime_pay'],
                        'hours' => $overtimeHours,
                        'rate'  => $overtimeRate,
                    ],
                    'bonuses'    => $calculation['bonuses'],
                    'gross_total'=> $calculation['gross_salary'],
                ],
                'deductions_breakdown' => [
                    'paye'               => $calculation['deductions']['paye_tax'],
                    'statutory_breakdown'=> $calculation['deductions']['statutory'],
                    'statutory_total'    => $calculation['deductions']['total_statutory'],
                    'other_deductions'   => 0.0,
                    'total_deductions'   => $calculation['deductions']['total_deductions'],
                ],
                'employer_costs'   => $calculation['employer_costs'],
                'net_calculation'  => [
                    'gross'      => $calculation['gross_salary'],
                    'deductions' => $calculation['deductions']['total_deductions'],
                    'net'        => $calculation['net_salary'],
                ],
            ],
        ]);

        $payslip->load('employee.user');

        Log::info('Payslip created successfully', [
            'payslip_id'  => $payslip->id,
            'employee_id' => $employee->id,
            'gross_salary'=> $calculation['gross_salary'],
            'net_pay'     => $calculation['net_salary'],
        ]);

        try {
            event(new PayslipGenerated($payslip));
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch PayslipGenerated event:', [
                'error' => $e->getMessage(),
            ]);
        }

        // Generate PDF if requested (default true)
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

    /**
     * Show single payslip details - trait auto-decrypts financial fields
     */
    public function show(Payslip $payslip): JsonResponse
    {
        $user = request()->user();

        if (!$user->isAdmin() && $payslip->employee->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $payslip->load(['employee.user', 'employee.business']);

        // toDetailedArray() accesses encrypted fields as properties;
        // the trait decrypts them transparently via getAttribute().
        return response()->json(['data' => $payslip->toDetailedArray()]);
    }

    /**
     * Format payslip for list response.
     *
     * FIX: was calling $payslip->usesDynamicTaxConfig() — this method does not
     * exist. The model defines a getUsesDynamicTaxConfigAttribute() accessor,
     * which Laravel exposes as the snake_case property uses_dynamic_tax_config.
     * All other financial field accesses also go through the trait's accessor,
     * so casting to float is sufficient — no manual decryption required here.
     */
    private function formatPayslipForList(Payslip $payslip): array
    {
        $period = 'N/A';
        if ($payslip->pay_period_start) {
            $period = $payslip->pay_period_start->format('M Y');
        } elseif ($payslip->created_at) {
            $period = $payslip->created_at->format('M Y');
        }

        return [
            'id'              => $payslip->id,
            'employee_id'     => $payslip->employee->employee_id ?? 'N/A',
            'employee_name'   => $payslip->employee->full_name ?? 'N/A',
            'department'      => $payslip->employee->department ?? 'N/A',
            'business_name'   => $payslip->employee->business->name ?? 'N/A',
            'period'          => $period,
            'pay_period_start'=> $payslip->pay_period_start?->format('Y-m-d'),
            'pay_period_end'  => $payslip->pay_period_end?->format('Y-m-d'),
            'payment_date'    => $payslip->payment_date?->format('Y-m-d'),

            // Trait decrypts these transparently when accessed as properties
            'basic_salary'    => (float) $payslip->basic_salary,
            'gross_salary'    => (float) $payslip->gross_salary,
            'net_pay'         => (float) $payslip->net_pay,
            'total_deductions'=> (float) $payslip->total_deductions,

            'currency'        => $payslip->currency,
            'status'          => $payslip->status ?? 'generated',
            'is_sent'         => $payslip->is_sent ?? false,
            'pdf_available'   => !empty($payslip->pdf_path),
            'pdf_path'        => $payslip->pdf_path,

            // FIX: was usesDynamicTaxConfig() — must be property access, not method call.
            // getUsesDynamicTaxConfigAttribute() → accessed as uses_dynamic_tax_config
            'uses_dynamic_config' => $payslip->uses_dynamic_tax_config,
        ];
    }

    /**
     * Generate PDF for a single payslip
     */
    public function generatePdf(Payslip $payslip): JsonResponse
    {
        try {
            $payslip->load(['employee.user', 'employee.business']);
            $path = $this->payslipGenerator->generatePdf($payslip);

            return response()->json([
                'message'      => 'PDF generated successfully',
                'pdf_available'=> true,
                'path'         => $path,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'PDF generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk generate payslips via Payroll Run
     */
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

    /**
     * Bulk download as ZIP
     */
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

    /**
     * Send email notification for a payslip
     */
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

    /**
     * Download PDF file
     */
    public function download(Payslip $payslip)
    {
        $user = request()->user();

        if (!$user->isAdmin() && $payslip->employee->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (empty($payslip->pdf_path) || !Storage::exists($payslip->pdf_path)) {
            try {
                $this->payslipGenerator->generatePdf($payslip);
                $payslip->refresh();
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'PDF not found and could not be generated.',
                ], 404);
            }
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

        return Storage::download($payslip->pdf_path, $filename);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Map statutory deductions array to legacy DB columns (NAPSA / NHIMA / Pension).
     * These columns are encrypted by the trait; values passed here are plain floats.
     */
    private function mapStatutoryToLegacyColumns(array $statutory): array
    {
        $col = collect($statutory);

        $napsa = $col->where('type', 'levy')
            ->filter(fn($i) => stripos($i['name'], 'NAPSA') !== false)
            ->sum('amount');

        $nhima = $col->where('type', 'health')->sum('amount');

        $pension = $col->where('type', 'pension')
            ->filter(fn($i) => stripos($i['name'], 'NAPSA') === false)
            ->sum('amount');

        return [
            'napsa'   => round($napsa, 2),
            'nhima'   => round($nhima, 2),
            'pension' => round($pension, 2),
        ];
    }

    /**
     * Determine the human-readable type of a TaxConfiguration record.
     */
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

    /**
     * Log which tax configuration was selected and why.
     */
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
            'tax_config_business_id' => $taxConfig->business_id ?? 'all',
            'currency'               => $taxConfig->getCurrency(),
        ]);
    }

    /**
     * Build a descriptive 422 response when no tax configuration can be found.
     */
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