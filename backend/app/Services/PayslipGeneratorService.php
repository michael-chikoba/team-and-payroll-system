<?php

namespace App\Services;

use App\Jobs\GeneratePayslipPdf;
use App\Models\Payslip;
use App\Models\Payroll;
use App\Models\SystemSetting; // Import SystemSetting
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

        // 1. GET DYNAMIC BUSINESS ADDRESS
        $businessId = $employee->business_id;
        $countryCode = $employee->business->country_code ?? 'ZM'; // Default fallback
        
        // Fetch address from SystemSetting (Business specific -> Country specific -> Default)
        $companyName = $employee->business->name ?? 'Castle Holdings Ltd';
        $companyAddress = SystemSetting::where('key', 'company_address')
            ->where(function($q) use ($businessId, $countryCode) {
                $q->where('business_id', $businessId)
                  ->orWhere('country_code', $countryCode);
            })
            ->orderByDesc('business_id') // Prefer business specific
            ->value('value');

        if (!$companyAddress) {
            $companyAddress = "54 Seble Road, Lusaka, Zambia"; // Hard fallback
        }

        // 2. PREPARE DYNAMIC EARNINGS LIST
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

        // 3. PREPARE DYNAMIC DEDUCTIONS LIST
        $deductionsList = [];
        
        // A. PAYE (Always first)
        if ($payslip->paye > 0) {
            $deductionsList[] = ['name' => 'PAYE Tax', 'amount' => $payslip->paye];
        }

        // B. Dynamic Statutory Deductions from Breakdown
        // This handles NAPSA, NHIMA, Pension, etc. dynamically based on config
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

        // 4. ALIGN LISTS FOR PDF TABLE (Side by Side)
        // We need rows to match in length for the HTML table
        $maxRows = max(count($earningsList), count($deductionsList));
        $tableRows = [];

        for ($i = 0; $i < $maxRows; $i++) {
            $tableRows[] = [
                'earning' => $earningsList[$i] ?? null,
                'deduction' => $deductionsList[$i] ?? null,
            ];
        }

        // Handle payroll_period string
        $payrollPeriod = $payroll->payroll_period ?? $payslip->pay_period_start?->format('Y-m');
        
        // Mock payroll object if missing
        if (!$payroll) {
            $payroll = (object) [
                'pay_period' => $payslip->pay_period_start ? $payslip->pay_period_start->format('F Y') : now()->format('F Y'),
                'working_days' => 22,
                'payroll_period' => $payrollPeriod,
            ];
        }
        
        // Template Data
        $templateData = [
            'payslip' => $payslip,
            'employee' => $employee,
            'payroll' => $payroll,
            'company_name' => $companyName,
            'company_address' => $companyAddress,
            'table_rows' => $tableRows, // The pre-processed side-by-side rows
            'breakdown' => $breakdown,
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
            
            return $path;
            
        } catch (\Exception $e) {
            Log::error('PayslipGeneratorService: PDF generation failed', [
                'payslip_id' => $payslip->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    // ... (Keep existing generateForPayroll, bulkDownload, cleanupTempFiles methods as they were) ...
    
    public function generateForPayroll(Payroll $payroll): void
    {
        $payslips = $payroll->payslips()->whereNull('pdf_path')->get();
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
        }
        
        return $zipFilename;
    }
    
    public function cleanupTempFiles(string $zipPath): void
    {
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }
    }
}