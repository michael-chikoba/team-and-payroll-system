<?php

namespace App\Services;

use App\Jobs\GeneratePayslipPdf;
use App\Models\Payslip;
use App\Models\Payroll;
use App\Models\SystemSetting;
use App\Models\TaxConfiguration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class PayslipGeneratorService
{
    public function generatePdf(Payslip $payslip): string
    {
        // Load relationships
        $payslip->load(['employee.user', 'employee.business', 'payroll']);
        
        $employee = $payslip->employee;
        $payroll = $payslip->payroll;
        $breakdown = $payslip->breakdown ?? [];

        // 1. GET CURRENCY INFORMATION
        $currencyInfo = $this->getCurrencyInfo($payslip, $employee);

        // 2. GET DYNAMIC BUSINESS ADDRESS
        $businessId = $employee->business_id;
        $countryCode = $employee->business->country_code ?? $employee->getCountryCode() ?? 'ZM';
        
        // Fetch address from SystemSetting
        $companyName = $employee->business->name ?? 'Castle Holdings Ltd';
        $companyAddress = SystemSetting::where('key', 'company_address')
            ->where(function($q) use ($businessId, $countryCode) {
                $q->where('business_id', $businessId)
                  ->orWhere('country_code', $countryCode);
            })
            ->orderByDesc('business_id')
            ->value('value');

        if (!$companyAddress) {
            $companyAddress = "54 Seble Road, Lusaka, Zambia";
        }

        // 3. PREPARE DYNAMIC EARNINGS LIST
        $earningsList = [];
        $earningsList[] = ['name' => 'Basic Salary', 'amount' => $payslip->basic_salary];
        
        if ($payslip->house_allowance > 0) {
            $earningsList[] = ['name' => 'Housing Allowance', 'amount' => $payslip->house_allowance];
        }
        if ($payslip->transport_allowance > 0) {
            $earningsList[] = ['name' => 'Transport Allowance', 'amount' => $payslip->transport_allowance];
        }
        if ($payslip->other_allowances > 0) {
            $earningsList[] = ['name' => 'Lunch/Other Allowance', 'amount' => $payslip->other_allowances];
        }
        if ($payslip->overtime_pay > 0) {
            $earningsList[] = ['name' => "Overtime Pay ({$payslip->overtime_hours} hrs)", 'amount' => $payslip->overtime_pay];
        }
        if ($payslip->bonuses > 0) {
            $earningsList[] = ['name' => 'Bonuses', 'amount' => $payslip->bonuses];
        }

        // 4. PREPARE DYNAMIC DEDUCTIONS LIST
        $deductionsList = [];
        
        // A. PAYE (Always first)
        if ($payslip->paye > 0) {
            $deductionsList[] = ['name' => 'PAYE Tax', 'amount' => $payslip->paye];
        }

        // B. Dynamic Statutory Deductions from Breakdown
        $statutoryDeductions = $breakdown['deductions_breakdown']['statutory_breakdown'] ?? [];
        
        if (!empty($statutoryDeductions)) {
            foreach ($statutoryDeductions as $deduction) {
                $deductionsList[] = [
                    'name' => $deduction['name'], 
                    'amount' => $deduction['amount']
                ];
            }
        } else {
            // Fallback for old records without breakdown structure
            if ($payslip->napsa > 0) $deductionsList[] = ['name' => 'NAPSA', 'amount' => $payslip->napsa];
            if ($payslip->nhima > 0) $deductionsList[] = ['name' => 'NHIMA', 'amount' => $payslip->nhima];
            if ($payslip->pension > 0) $deductionsList[] = ['name' => 'Pension', 'amount' => $payslip->pension];
        }

        // C. Other Deductions
        if ($payslip->other_deductions > 0) {
            $deductionsList[] = ['name' => 'Other Deductions', 'amount' => $payslip->other_deductions];
        }

        // 5. ALIGN LISTS FOR PDF TABLE (Side by Side)
        $maxRows = max(count($earningsList), count($deductionsList));
        $tableRows = [];

        for ($i = 0; $i < $maxRows; $i++) {
            $tableRows[] = [
                'earning' => $earningsList[$i] ?? null,
                'deduction' => $deductionsList[$i] ?? null,
            ];
        }

        // 6. Handle payroll_period string
        $payrollPeriod = $payroll->payroll_period ?? $payslip->pay_period_start?->format('Y-m');
        
        // Mock payroll object if missing
        if (!$payroll) {
            $payroll = (object) [
                'pay_period' => $payslip->pay_period_start ? $payslip->pay_period_start->format('F Y') : now()->format('F Y'),
                'working_days' => 22,
                'payroll_period' => $payrollPeriod,
            ];
        }
        
        // 7. Template Data
        $templateData = [
            'payslip' => $payslip,
            'employee' => $employee,
            'payroll' => $payroll,
            'company_name' => $companyName,
            'company_address' => $companyAddress,
            'table_rows' => $tableRows,
            'breakdown' => $breakdown,
            'currency_info' => $currencyInfo, // Added currency info
        ];
        
        try {
            $pdf = PDF::loadView('pdf.payslip-template', $templateData);
            $pdf->setPaper('A4', 'portrait');
            
            $filename = "payslip-{$payslip->id}-{$payrollPeriod}.pdf";
            $path = "payslips/{$filename}";
            
            Storage::put($path, $pdf->output());
            
            $payslip->update([
                'pdf_path' => $path,
                'status' => $payslip->status === 'draft' ? 'generated' : $payslip->status
            ]);
            
            Log::info('PDF generated successfully', [
                'payslip_id' => $payslip->id,
                'currency' => $currencyInfo['code'],
                'path' => $path
            ]);
            
            return $path;
            
        } catch (\Exception $e) {
            Log::error('PayslipGeneratorService: PDF generation failed', [
                'payslip_id' => $payslip->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get currency information for the payslip
     */
    private function getCurrencyInfo(Payslip $payslip, $employee): array
    {
        // Try to get currency from breakdown first (most reliable)
        $breakdown = $payslip->breakdown ?? [];
        
        if (isset($breakdown['currency'])) {
            $currencyCode = $breakdown['currency'];
            Log::debug('Currency from breakdown', ['currency' => $currencyCode]);
        } 
        // Otherwise, try to get from tax configuration
        elseif (isset($breakdown['tax_config_id'])) {
            $taxConfig = TaxConfiguration::find($breakdown['tax_config_id']);
            $currencyCode = $taxConfig ? $taxConfig->getCurrency() : null;
            Log::debug('Currency from tax config', ['currency' => $currencyCode]);
        }
        // Last resort: get from employee's country or business
        else {
            $businessId = $employee->business_id;
            $countryCode = $employee->getCountryCode();
            
            $taxConfig = TaxConfiguration::getForBusinessAndCountry($businessId, $countryCode);
            $currencyCode = $taxConfig ? $taxConfig->getCurrency() : 'USD';
            
            Log::debug('Currency from fallback lookup', [
                'business_id' => $businessId,
                'country_code' => $countryCode,
                'currency' => $currencyCode
            ]);
        }

        // Get symbol using TaxConfiguration's static method
        $symbol = TaxConfiguration::getCurrencySymbolByCode($currencyCode);

        return [
            'code' => $currencyCode,
            'symbol' => $symbol,
            'name' => $this->getCurrencyName($currencyCode),
        ];
    }

    /**
     * Get currency name from code
     */
    private function getCurrencyName(string $code): string
    {
        $names = [
    // 🌍 Major Global Currencies
    'USD' => 'US Dollar',
    'EUR' => 'Euro',
    'GBP' => 'British Pound',
    'JPY' => 'Japanese Yen',
    'CNY' => 'Chinese Yuan',
    'INR' => 'Indian Rupee',
    'AUD' => 'Australian Dollar',
    'CAD' => 'Canadian Dollar',

    // 🇿🇦 Southern Africa
    'ZMW' => 'Zambian Kwacha',
    'ZAR' => 'South African Rand',
    'NAD' => 'Namibian Dollar',
    'BWP' => 'Botswana Pula',
    'MWK' => 'Malawian Kwacha',
    'LSL' => 'Lesotho Loti',
    'SZL' => 'Eswatini Lilangeni',
    'MZN' => 'Mozambican Metical',
    'ZWL' => 'Zimbabwean Dollar',

    // 🇰🇪 East Africa
    'KES' => 'Kenyan Shilling',
    'UGX' => 'Ugandan Shilling',
    'TZS' => 'Tanzanian Shilling',
    'RWF' => 'Rwandan Franc',
    'BIF' => 'Burundian Franc',
    'ETB' => 'Ethiopian Birr',
    'SOS' => 'Somali Shilling',

    // 🇳🇬 West Africa
    'NGN' => 'Nigerian Naira',
    'GHS' => 'Ghanaian Cedi',
    'XOF' => 'West African CFA Franc',
    'XAF' => 'Central African CFA Franc',
    'SLL' => 'Sierra Leonean Leone',
    'GMD' => 'Gambian Dalasi',
    'LRD' => 'Liberian Dollar',

    // 🌍 Central Africa
    'CDF' => 'Congolese Franc',
    'STD' => 'São Tomé and Príncipe Dobra',

    // 🌍 North Africa
    'EGP' => 'Egyptian Pound',
    'MAD' => 'Moroccan Dirham',
    'DZD' => 'Algerian Dinar',
    'TND' => 'Tunisian Dinar',
    'LYD' => 'Libyan Dinar',

    // 🌍 Island Nations
    'MUR' => 'Mauritian Rupee',
    'SCR' => 'Seychellois Rupee',
    'CVE' => 'Cape Verdean Escudo',
];


        return $names[$code] ?? $code;
    }
    
    /**
     * Generate payslips for an entire payroll
     */
    public function generateForPayroll(Payroll $payroll): void
    {
        $payslips = $payroll->payslips()->whereNull('pdf_path')->get();
        
        Log::info('Starting bulk PDF generation', [
            'payroll_id' => $payroll->id,
            'payslip_count' => $payslips->count()
        ]);
        
        foreach ($payslips as $payslip) {
            GeneratePayslipPdf::dispatch($payslip);
        }
    }
    
    /**
     * Create bulk download ZIP
     */
    public function bulkDownload(Payroll $payroll): string
    {
        $payslips = $payroll->payslips()->whereNotNull('pdf_path')->get();
        
        if ($payslips->isEmpty()) {
            throw new \Exception('No payslips with PDFs available for download');
        }
        
        $zip = new ZipArchive();
        $zipFilename = storage_path("app/temp/payslips-{$payroll->id}-" . now()->format('Y-m-d') . '.zip');
        
        if (!is_dir(dirname($zipFilename))) {
            mkdir(dirname($zipFilename), 0755, true);
        }
        
        if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($payslips as $payslip) {
                if (Storage::exists($payslip->pdf_path)) {
                    $employeeName = str_replace(' ', '_',
                        ($payslip->employee->user->first_name ?? 'unknown') . '_' .
                        ($payslip->employee->user->last_name ?? '')
                    );
                    $filename = "payslip-{$employeeName}-{$payroll->payroll_period}.pdf";
                    $zip->addFile(storage_path("app/{$payslip->pdf_path}"), $filename);
                }
            }
            $zip->close();
            
            Log::info('Bulk download ZIP created', [
                'payroll_id' => $payroll->id,
                'payslip_count' => $payslips->count(),
                'zip_path' => $zipFilename
            ]);
        }
        
        return $zipFilename;
    }
    
    /**
     * Clean up temporary files
     */
    public function cleanupTempFiles(string $zipPath): void
    {
        if (file_exists($zipPath)) {
            unlink($zipPath);
            Log::info('Temporary ZIP file deleted', ['path' => $zipPath]);
        }
    }
}