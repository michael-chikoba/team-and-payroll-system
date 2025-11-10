<?php

namespace App\Services;

use App\Jobs\GeneratePayslipPdf;
use App\Models\Payslip;
use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class PayslipGeneratorService
{
    public function generatePdf(Payslip $payslip): string
    {
        // Load relationships
        $payslip->load(['employee.user', 'payroll']);
        
        // Extract needed variables for the Blade template
        $employee = $payslip->employee;
        $payroll = $payslip->payroll;
        
        // Handle payroll_period: use pay_period_start if no payroll
        $payrollPeriod = $payslip->payroll?->payroll_period ?? $payslip->pay_period_start?->format('Y-m');
        
        // Create a mock payroll object if none exists (for standalone payslips)
        if (!$payroll) {
            $payroll = (object) [
                'pay_period' => $payslip->pay_period_start 
                    ? $payslip->pay_period_start->format('F Y') 
                    : now()->format('F Y'),
                'processed_at' => $payslip->created_at ?? now(),
                'working_days' => 22, // Default working days
            ];
        }
        
        // Prepare bonuses and deductions as arrays for the template
        // Handle if they're JSON strings, arrays, or null
        $bonuses = $this->prepareJsonField($payslip->bonuses ?? $payslip->bonus_details);
        $deductions = $this->prepareJsonField($payslip->deductions ?? $payslip->deduction_details);
        
        // Create a modified payslip object with decoded arrays
        $payslipData = clone $payslip;
        $payslipData->bonuses = $bonuses;
        $payslipData->deductions = $deductions;
        
        // Pass all required variables to the view
        $pdf = PDF::loadView('pdf.payslip-template', [
            'payslip' => $payslipData,
            'employee' => $employee,
            'payroll' => $payroll,
        ]);
        
        $filename = "payslip-{$payslip->id}-{$payrollPeriod}.pdf";
        $path = "payslips/{$filename}";
        
        Storage::put($path, $pdf->output());
        
        // Update the payslip record with PDF path
        $payslip->update(['pdf_path' => $path, 'status' => 'generated']);
        
        return $path;
    }
    
    /**
     * Prepare JSON field for template use
     * Handles JSON strings, arrays, or null values
     */
    private function prepareJsonField($field): array
    {
        if (is_null($field)) {
            return [];
        }
        
        if (is_string($field)) {
            $decoded = json_decode($field, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        if (is_array($field)) {
            return $field;
        }
        
        return [];
    }

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
        
        // Ensure directory exists
        if (!is_dir(dirname($zipFilename))) {
            mkdir(dirname($zipFilename), 0755, true);
        }
        
        if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($payslips as $payslip) {
                if (Storage::exists($payslip->pdf_path)) {
                    $employeeName = str_replace(' ', '_', $payslip->employee->user->name ?? 'unknown');
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