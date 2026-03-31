<?php

namespace App\Services;

use App\Jobs\GeneratePayslipPdf;
use App\Models\Payslip;
use App\Models\Payroll;
use App\Models\SystemSetting;
use App\Models\TaxConfiguration;
use App\Services\EncryptionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class PayslipGeneratorService
{
    /**
     * Generate a PDF for the given payslip.
     *
     * ROOT CAUSE OF THE ADMIN BLANK PAYSLIP BUG:
     * ─────────────────────────────────────────────────────────────────────────
     * getForcedDecrypted() calls EncryptionService::decryptRaw() which uses
     * Crypt::decryptString(). In certain contexts (queued jobs, admin-triggered
     * regenerations where the APP_KEY or the encrypted payload was written by
     * a different request cycle) this can return null or throw silently,
     * causing all financial fields to resolve to 0.
     *
     * The PDF was then stored to disk at that broken state and served forever
     * by the download() endpoint because it found an existing pdf_path and
     * short-circuited without regenerating.
     *
     * THE FIX (two-layer decryption with breakdown fallback):
     * ─────────────────────────────────────────────────────────────────────────
     * resolveFinancialField() tries getForcedDecrypted() first, then falls
     * back to the breakdown JSON which is stored as plain (unencrypted) JSON.
     * The breakdown was written at payslip-creation time with the same values
     * that were encrypted into the columns, making it a reliable source of
     * truth for PDF rendering.
     *
     * Additionally, PayslipController::download() now ALWAYS regenerates the
     * PDF instead of serving the cached file, so stale broken PDFs on disk
     * can never be served again.
     *
     * @return string  Internal storage path (never returned to clients directly)
     */
    public function generatePdf(Payslip $payslip): string
    {
        $payslip->load(['employee.user', 'employee.business', 'payroll']);

        $employee  = $payslip->employee;
        $payroll   = $payslip->payroll;
        $breakdown = $payslip->breakdown ?? [];

        // ── 1. Currency ───────────────────────────────────────────────────────
        $currencyInfo = $this->getCurrencyInfo($payslip, $employee);

        // ── 2. Company details ────────────────────────────────────────────────
        $businessId     = $employee->business_id;
        $countryCode    = $employee->business->country_code ?? $employee->getCountryCode() ?? 'ZM';
        $companyName    = $employee->business->name ?? 'Castle Holdings Ltd';
        $companyAddress = SystemSetting::where('key', 'company_address')
            ->where(function ($q) use ($businessId, $countryCode) {
                $q->where('business_id', $businessId)
                    ->orWhere('country_code', $countryCode);
            })
            ->orderByDesc('business_id')
            ->value('value') ?? '54 Seble Road, Lusaka, Zambia';

        // ── 3. Resolve all financial fields with two-layer fallback ───────────
        //
        // Layer 1: getForcedDecrypted() — bypasses role gate, reads ciphertext
        //          directly via EncryptionService::decryptRaw().
        // Layer 2: breakdown JSON — plain unencrypted JSON written at creation
        //          time. Used when Layer 1 returns null/0, which happens when
        //          the encrypted column value is corrupted, missing, or was
        //          written with a different key.
        //
        // This two-layer approach means the PDF renderer ALWAYS gets real
        // financial values regardless of context (queued job, admin request,
        // employee download).
        //
        $earningsBreakdown   = $breakdown['earnings_breakdown']           ?? [];
        $deductionsBreakdown = $breakdown['deductions_breakdown']         ?? [];
        $allowances          = $earningsBreakdown['allowances']           ?? [];
        $overtimeFromBreakdown = $earningsBreakdown['overtime']           ?? [];

        $f = [
            'basic_salary'        => $this->resolveFinancialField($payslip, 'basic_salary',
                                        $earningsBreakdown['basic_salary'] ?? null),

            'house_allowance'     => $this->resolveFinancialField($payslip, 'house_allowance',
                                        $allowances['housing'] ?? null),

            'transport_allowance' => $this->resolveFinancialField($payslip, 'transport_allowance',
                                        $allowances['transport'] ?? null),

            'other_allowances'    => $this->resolveFinancialField($payslip, 'other_allowances',
                                        $allowances['lunch'] ?? null),

            // overtime_hours is NOT encrypted — read directly from attributes
            'overtime_hours'      => (float) ($payslip->attributes['overtime_hours'] ?? 0),

            'overtime_rate'       => $this->resolveFinancialField($payslip, 'overtime_rate',
                                        $overtimeFromBreakdown['rate'] ?? null),

            'overtime_pay'        => $this->resolveFinancialField($payslip, 'overtime_pay',
                                        $overtimeFromBreakdown['pay'] ?? $earningsBreakdown['overtime']['pay'] ?? null),

            // bonuses is NOT encrypted — read directly from attributes
            'bonuses'             => (float) ($payslip->attributes['bonuses'] ?? $earningsBreakdown['bonuses'] ?? 0),

            'gross_salary'        => $this->resolveFinancialField($payslip, 'gross_salary',
                                        $earningsBreakdown['gross_total'] ?? $breakdown['net_calculation']['gross'] ?? null),

            'gross_pay'           => $this->resolveFinancialField($payslip, 'gross_pay',
                                        $earningsBreakdown['gross_total'] ?? null),

            'tax_deductions'      => $this->resolveFinancialField($payslip, 'tax_deductions',
                                        $deductionsBreakdown['paye'] ?? null),

            'napsa'               => $this->resolveFinancialField($payslip, 'napsa', null),

            'paye'                => $this->resolveFinancialField($payslip, 'paye',
                                        $deductionsBreakdown['paye'] ?? null),

            'nhima'               => $this->resolveFinancialField($payslip, 'nhima', null),

            'pension'             => $this->resolveFinancialField($payslip, 'pension', null),

            'other_deductions'    => $this->resolveFinancialField($payslip, 'other_deductions',
                                        $deductionsBreakdown['other_deductions'] ?? 0),

            'total_deductions'    => $this->resolveFinancialField($payslip, 'total_deductions',
                                        $deductionsBreakdown['total_deductions'] ?? $breakdown['net_calculation']['deductions'] ?? null),

            'net_pay'             => $this->resolveFinancialField($payslip, 'net_pay',
                                        $breakdown['net_calculation']['net'] ?? null),
        ];

        // ── 3b. Derive napsa/nhima/pension from statutory_breakdown if still 0 ─
        //
        // statutory_breakdown in the breakdown JSON is the most granular
        // source for per-deduction amounts. Use it as a tertiary fallback
        // for the legacy NAPSA/NHIMA/Pension columns when both encrypted
        // column AND the legacy breakdown fields are 0.
        //
        $statutoryBreakdown = $deductionsBreakdown['statutory_breakdown'] ?? [];
        if (!empty($statutoryBreakdown)) {
            if ($f['napsa'] == 0) {
                $f['napsa'] = (float) collect($statutoryBreakdown)
                    ->where('type', 'levy')
                    ->filter(fn($i) => stripos($i['name'] ?? '', 'NAPSA') !== false)
                    ->sum('amount');
            }
            if ($f['nhima'] == 0) {
                $f['nhima'] = (float) collect($statutoryBreakdown)
                    ->where('type', 'health')
                    ->sum('amount');
            }
            if ($f['pension'] == 0) {
                $f['pension'] = (float) collect($statutoryBreakdown)
                    ->where('type', 'pension')
                    ->filter(fn($i) => stripos($i['name'] ?? '', 'NAPSA') === false)
                    ->sum('amount');
            }
        }

        // status is also encrypted; fall back to 'generated' if unreadable
        $status = 'generated';
        try {
            $decryptedStatus = $payslip->getForcedDecrypted('status');
            if (!empty($decryptedStatus)) {
                $status = (string) $decryptedStatus;
            }
        } catch (\Exception $e) {
            Log::warning('Could not decrypt status field, using default', [
                'payslip_id' => $payslip->id,
                'error'      => $e->getMessage(),
            ]);
        }

        // ── 4. Sanity-check: log a warning if critical fields are still 0 ─────
        if ($f['basic_salary'] == 0 && $f['net_pay'] == 0) {
            Log::warning('PayslipGeneratorService: ALL financial fields resolved to 0 — PDF may be incomplete', [
                'payslip_id'        => $payslip->id,
                'has_breakdown'     => !empty($breakdown),
                'breakdown_keys'    => array_keys($breakdown),
            ]);
        } else {
            Log::info('PayslipGeneratorService: financial fields resolved', [
                'payslip_id'   => $payslip->id,
                'basic_salary' => $f['basic_salary'],
                'gross_salary' => $f['gross_salary'],
                'net_pay'      => $f['net_pay'],
            ]);
        }

        // ── 5. Earnings rows ──────────────────────────────────────────────────
        $earningsList   = [];
        $earningsList[] = ['name' => 'Basic Salary', 'amount' => $f['basic_salary']];

        if ($f['house_allowance']     > 0) $earningsList[] = ['name' => 'Housing Allowance',     'amount' => $f['house_allowance']];
        if ($f['transport_allowance'] > 0) $earningsList[] = ['name' => 'Transport Allowance',   'amount' => $f['transport_allowance']];
        if ($f['other_allowances']    > 0) $earningsList[] = ['name' => 'Lunch/Other Allowance', 'amount' => $f['other_allowances']];
        if ($f['overtime_pay']        > 0) $earningsList[] = ['name' => "Overtime Pay ({$f['overtime_hours']} hrs)", 'amount' => $f['overtime_pay']];
        if ($f['bonuses']             > 0) $earningsList[] = ['name' => 'Bonuses',               'amount' => $f['bonuses']];

        // ── 6. Deductions rows ────────────────────────────────────────────────
        $deductionsList = [];

        if ($f['paye'] > 0) {
            $deductionsList[] = ['name' => 'PAYE Tax', 'amount' => $f['paye']];
        }

        // Prefer the rich statutory_breakdown from the breakdown JSON
        if (!empty($statutoryBreakdown)) {
            foreach ($statutoryBreakdown as $deduction) {
                $deductionsList[] = [
                    'name'   => $deduction['name'],
                    'amount' => (float) ($deduction['amount'] ?? 0),
                ];
            }
        } else {
            // Fall back to legacy columns
            if ($f['napsa']   > 0) $deductionsList[] = ['name' => 'NAPSA',   'amount' => $f['napsa']];
            if ($f['nhima']   > 0) $deductionsList[] = ['name' => 'NHIMA',   'amount' => $f['nhima']];
            if ($f['pension'] > 0) $deductionsList[] = ['name' => 'Pension', 'amount' => $f['pension']];
        }

        if ($f['other_deductions'] > 0) {
            $deductionsList[] = ['name' => 'Other Deductions', 'amount' => $f['other_deductions']];
        }

        // ── 7. Side-by-side table rows ────────────────────────────────────────
        $maxRows   = max(count($earningsList), count($deductionsList), 1);
        $tableRows = [];
        for ($i = 0; $i < $maxRows; $i++) {
            $tableRows[] = [
                'earning'   => $earningsList[$i]   ?? null,
                'deduction' => $deductionsList[$i] ?? null,
            ];
        }

        // ── 8. Payroll period ─────────────────────────────────────────────────
        $payrollPeriod = $payroll->payroll_period
            ?? $payslip->pay_period_start?->format('Y-m')
            ?? now()->format('Y-m');

        if (!$payroll) {
            $payroll = (object) [
                'pay_period'     => $payslip->pay_period_start?->format('F Y') ?? now()->format('F Y'),
                'working_days'   => 22,
                'payroll_period' => $payrollPeriod,
            ];
        }

        // ── 9. Template data ──────────────────────────────────────────────────
        $templateData = [
            'payslip'          => $payslip,   // metadata only
            'employee'         => $employee,
            'payroll'          => $payroll,
            'status'           => $status,
            'financials'       => $f,
            'gross_salary'     => $f['gross_salary'],
            'total_deductions' => $f['total_deductions'],
            'net_pay'          => $f['net_pay'],
            'table_rows'       => $tableRows,
            'earnings_list'    => $earningsList,
            'deductions_list'  => $deductionsList,
            'company_name'     => $companyName,
            'company_address'  => $companyAddress,
            'breakdown'        => $breakdown,
            'currency_info'    => $currencyInfo,
        ];

        // ── 10. Render & persist ───────────────────────────────────────────────
        try {
            $pdf = PDF::loadView('pdf.payslip-template', $templateData);
            $pdf->setPaper('A4', 'portrait');

            $filename = "payslip-{$payslip->id}-{$payrollPeriod}.pdf";
            $path     = "payslips/{$filename}";

            Storage::put($path, $pdf->output());

            // Raw DB update — avoids re-triggering encrypted-field hooks
            DB::table('payslips')
                ->where('id', $payslip->id)
                ->update(['pdf_path' => $path]);

            // Advance status from draft → generated if needed
            if ($status === 'draft') {
                DB::table('payslips')
                    ->where('id', $payslip->id)
                    ->update([
                        'status' => app(EncryptionService::class)->encrypt('generated'),
                    ]);
            }

            $payslip->refresh();

            Log::info('PDF generated successfully', [
                'payslip_id' => $payslip->id,
                'currency'   => $currencyInfo['code'],
                'net_pay'    => $f['net_pay'],
            ]);

            return $path;
        } catch (\Exception $e) {
            Log::error('PayslipGeneratorService: PDF generation failed', [
                'payslip_id' => $payslip->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    // =========================================================================
    // PRIVATE: TWO-LAYER FIELD RESOLVER
    // =========================================================================

    /**
     * Resolve a single financial field using two layers:
     *
     * Layer 1 — getForcedDecrypted($field):
     *   Bypasses the EncryptionService role gate and calls Crypt::decryptString()
     *   directly. Returns null if the ciphertext is unreadable.
     *
     * Layer 2 — $breakdownFallback:
     *   The value from the breakdown JSON, passed in by the caller.
     *   breakdown is stored as plain JSON (not encrypted), written at payslip
     *   creation time with the same pre-encryption numeric values.
     *
     * If both return 0/null, returns 0.0 so the PDF still renders without
     * crashing — but a warning is logged upstream.
     *
     * @param  Payslip    $payslip
     * @param  string     $field            Encrypted column name
     * @param  mixed      $breakdownFallback Value from breakdown JSON (or null)
     * @return float
     */
    private function resolveFinancialField(Payslip $payslip, string $field, $breakdownFallback): float
    {
        // Layer 1: try encrypted column
        try {
            $decrypted = $payslip->getForcedDecrypted($field);
            if ($decrypted !== null && $decrypted !== '' && (float) $decrypted !== 0.0) {
                return (float) $decrypted;
            }
        } catch (\Exception $e) {
            Log::debug("resolveFinancialField: getForcedDecrypted failed for '{$field}'", [
                'payslip_id' => $payslip->id,
                'error'      => $e->getMessage(),
            ]);
        }

        // Layer 2: fall back to breakdown JSON value
        if ($breakdownFallback !== null && $breakdownFallback !== '' && (float) $breakdownFallback !== 0.0) {
            Log::debug("resolveFinancialField: using breakdown fallback for '{$field}'", [
                'payslip_id' => $payslip->id,
                'value'      => $breakdownFallback,
            ]);
            return (float) $breakdownFallback;
        }

        return 0.0;
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function getCurrencyInfo(Payslip $payslip, $employee): array
    {
        $breakdown = $payslip->breakdown ?? [];

        if (isset($breakdown['currency'])) {
            $currencyCode = $breakdown['currency'];
        } elseif (isset($breakdown['tax_config_id'])) {
            $taxConfig    = TaxConfiguration::find($breakdown['tax_config_id']);
            $currencyCode = $taxConfig ? $taxConfig->getCurrency() : 'ZMW';
        } else {
            $taxConfig    = TaxConfiguration::getForBusinessAndCountry(
                $employee->business_id,
                $employee->getCountryCode()
            );
            $currencyCode = $taxConfig ? $taxConfig->getCurrency() : 'ZMW';
        }

        return [
            'code'   => $currencyCode,
            'symbol' => TaxConfiguration::getCurrencySymbolByCode($currencyCode),
            'name'   => $this->getCurrencyName($currencyCode),
        ];
    }

    private function getCurrencyName(string $code): string
    {
        $names = [
            'USD' => 'US Dollar',              'EUR' => 'Euro',
            'GBP' => 'British Pound',          'JPY' => 'Japanese Yen',
            'CNY' => 'Chinese Yuan',           'INR' => 'Indian Rupee',
            'AUD' => 'Australian Dollar',      'CAD' => 'Canadian Dollar',
            'ZMW' => 'Zambian Kwacha',         'ZAR' => 'South African Rand',
            'NAD' => 'Namibian Dollar',        'BWP' => 'Botswana Pula',
            'MWK' => 'Malawian Kwacha',        'LSL' => 'Lesotho Loti',
            'SZL' => 'Eswatini Lilangeni',     'MZN' => 'Mozambican Metical',
            'ZWL' => 'Zimbabwean Dollar',      'KES' => 'Kenyan Shilling',
            'UGX' => 'Ugandan Shilling',       'TZS' => 'Tanzanian Shilling',
            'RWF' => 'Rwandan Franc',          'BIF' => 'Burundian Franc',
            'ETB' => 'Ethiopian Birr',         'SOS' => 'Somali Shilling',
            'NGN' => 'Nigerian Naira',         'GHS' => 'Ghanaian Cedi',
            'XOF' => 'West African CFA Franc', 'XAF' => 'Central African CFA Franc',
            'EGP' => 'Egyptian Pound',         'MAD' => 'Moroccan Dirham',
            'MUR' => 'Mauritian Rupee',        'SCR' => 'Seychellois Rupee',
        ];

        return $names[$code] ?? $code;
    }

    // =========================================================================
    // BULK OPERATIONS
    // =========================================================================

    public function generateForPayroll(Payroll $payroll): void
    {
        $payslips = $payroll->payslips()->whereNull('pdf_path')->get();

        Log::info('Starting bulk PDF generation', [
            'payroll_id'    => $payroll->id,
            'payslip_count' => $payslips->count(),
        ]);

        foreach ($payslips as $payslip) {
            GeneratePayslipPdf::dispatch($payslip);
        }
    }

    public function bulkDownload(Payroll $payroll): string
    {
        $payslips = $payroll->payslips()->whereNotNull('pdf_path')->get();

        if ($payslips->isEmpty()) {
            throw new \Exception('No payslips with PDFs available for download');
        }

        $zipDir      = storage_path('app/temp');
        $zipFilename = "{$zipDir}/payslips-{$payroll->id}-" . now()->format('Y-m-d') . '.zip';

        if (!is_dir($zipDir)) {
            mkdir($zipDir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Could not create ZIP archive');
        }

        foreach ($payslips as $payslip) {
            $rawPath = $payslip->attributes['pdf_path'] ?? null;
            if ($rawPath && Storage::exists($rawPath)) {
                $employeeName = str_replace(
                    ' ', '_',
                    ($payslip->employee->user->first_name ?? 'unknown') . '_' .
                    ($payslip->employee->user->last_name  ?? '')
                );
                $zip->addFile(
                    storage_path("app/{$rawPath}"),
                    "payslip-{$employeeName}-{$payroll->payroll_period}.pdf"
                );
            }
        }

        $zip->close();

        Log::info('Bulk download ZIP created', [
            'payroll_id'    => $payroll->id,
            'payslip_count' => $payslips->count(),
        ]);

        return $zipFilename;
    }

    public function cleanupTempFiles(string $zipPath): void
    {
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }
    }
}