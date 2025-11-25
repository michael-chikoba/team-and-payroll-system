<?php
namespace App\Services;

use App\Jobs\GeneratePayslipPdf;
use App\Models\Payslip;
use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
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
                'payroll_period' => $payrollPeriod,
            ];
        }
        
        // Prepare bonuses and deductions as arrays for the template
        $bonuses = $this->prepareJsonField($payslip->bonuses ?? $payslip->bonus_details);
        $deductions = $this->prepareJsonField($payslip->deductions ?? $payslip->deduction_details);
        
        // Get breakdown data
        $breakdown = $payslip->breakdown ?? [];
        
        // Create a modified payslip object with decoded arrays
        $payslipData = clone $payslip;
        $payslipData->bonuses = $bonuses;
        $payslipData->deductions = $deductions;
        $payslipData->breakdown = $breakdown;
        
        // Prepare comprehensive template data
        $templateData = [
            'payslip' => $payslipData,
            'employee' => $employee,
            'payroll' => $payroll,
            'breakdown' => $breakdown,
        ];
        
        // Log all data being sent to the template
        Log::info('PayslipGeneratorService: Preparing PDF generation', [
            'payslip_id' => $payslip->id,
            'employee_id' => $employee->id,
            'employee_data' => [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'user_id' => $employee->user_id,
                'first_name' => $employee->user->first_name ?? null,
                'last_name' => $employee->user->last_name ?? null,
                'full_name' => ($employee->user->first_name ?? '') . ' ' . ($employee->user->last_name ?? ''),
                'email' => $employee->user->email ?? null,
                'department' => $employee->department ?? null,
                'position' => $employee->position ?? null,
                'base_salary' => $employee->base_salary ?? null,
            ],
            'payslip_data' => [
                'id' => $payslip->id,
                'pay_period_start' => $payslip->pay_period_start?->format('Y-m-d'),
                'pay_period_end' => $payslip->pay_period_end?->format('Y-m-d'),
                'payment_date' => $payslip->payment_date?->format('Y-m-d'),
                'basic_salary' => $payslip->basic_salary,
                'house_allowance' => $payslip->house_allowance,
                'transport_allowance' => $payslip->transport_allowance,
                'other_allowances' => $payslip->other_allowances,
                'overtime_hours' => $payslip->overtime_hours,
                'overtime_rate' => $payslip->overtime_rate,
                'overtime_pay' => $payslip->overtime_pay,
                'gross_salary' => $payslip->gross_salary,
                'napsa' => $payslip->napsa,
                'paye' => $payslip->paye,
                'nhima' => $payslip->nhima,
                'other_deductions' => $payslip->other_deductions,
                'total_deductions' => $payslip->total_deductions,
                'net_pay' => $payslip->net_pay,
                'status' => $payslip->status,
                'bonuses' => $bonuses,
                'deductions' => $deductions,
            ],
            'payroll_data' => [
                'pay_period' => $payroll->pay_period ?? null,
                'payroll_period' => $payroll->payroll_period ?? $payrollPeriod,
                'processed_at' => $payroll->processed_at ?? null,
                'working_days' => $payroll->working_days ?? null,
            ],
            'breakdown_data' => $breakdown,
            'template_path' => 'pdf.payslip-template',
            'timestamp' => now()->toDateTimeString(),
        ]);
        
        try {
            // Generate PDF using the standardized template path
            $pdf = PDF::loadView('pdf.payslip-template', $templateData);
            
            // Set PDF options
            $pdf->setPaper('A4', 'portrait');
            
            $filename = "payslip-{$payslip->id}-{$payrollPeriod}.pdf";
            $path = "payslips/{$filename}";
            
            // Save the PDF
            Storage::put($path, $pdf->output());
            
            // Update the payslip record with PDF path
            $payslip->update([
                'pdf_path' => $path,
                'status' => 'generated'
            ]);
            
            // Log::info('PayslipGeneratorService: PDF generated successfully', [
            //     'payslip_id' => $payslip->id,
            //     'pdf_path' => $path,
            //     'filename' => $filename,
            //     'file_size' => Storage::size($path),
            //     'employee_name' => ($employee->user->first_name ?? '') . ' ' . ($employee->user->last_name ?? ''),
            // ]);
            
            return $path;
            
        } catch (\Exception $e) {
            Log::error('PayslipGeneratorService: PDF generation failed', [
                'payslip_id' => $payslip->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'template_data_keys' => array_keys($templateData),
            ]);
            
            throw $e;
        }
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
        
        // Log::info('PayslipGeneratorService: Bulk generation started for payroll', [
        //     'payroll_id' => $payroll->id,
        //     'payslips_count' => $payslips->count(),
        //     'payroll_period' => $payroll->payroll_period ?? null,
        // ]);
        
        foreach ($payslips as $payslip) {
            GeneratePayslipPdf::dispatch($payslip);
        }
        
        // Log::info('PayslipGeneratorService: Bulk generation jobs dispatched', [
        //     'payroll_id' => $payroll->id,
        //     'jobs_dispatched' => $payslips->count(),
        // ]);
    }
    
    public function bulkDownload(Payroll $payroll): string
    {
        $payslips = $payroll->payslips()->whereNotNull('pdf_path')->get();
        
        if ($payslips->isEmpty()) {
            Log::warning('PayslipGeneratorService: No payslips with PDFs for bulk download', [
                'payroll_id' => $payroll->id,
            ]);
            throw new \Exception('No payslips with PDFs available for download');
        }
        
        // Log::info('PayslipGeneratorService: Starting bulk download', [
        //     'payroll_id' => $payroll->id,
        //     'payslips_count' => $payslips->count(),
        // ]);
        
        $zip = new ZipArchive();
        $zipFilename = storage_path("app/temp/payslips-{$payroll->id}-" . now()->format('Y-m-d') . '.zip');
        
        // Ensure directory exists
        if (!is_dir(dirname($zipFilename))) {
            mkdir(dirname($zipFilename), 0755, true);
        }
        
        $successCount = 0;
        $failedCount = 0;
        
        if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($payslips as $payslip) {
                if (Storage::exists($payslip->pdf_path)) {
                    $employeeName = str_replace(' ', '_', 
                        ($payslip->employee->user->first_name ?? 'unknown') . '_' . 
                        ($payslip->employee->user->last_name ?? '')
                    );
                    $filename = "payslip-{$employeeName}-{$payroll->payroll_period}.pdf";
                    $zip->addFile(storage_path("app/{$payslip->pdf_path}"), $filename);
                    $successCount++;
                } else {
                    $failedCount++;
                    Log::warning('PayslipGeneratorService: PDF file not found for payslip', [
                        'payslip_id' => $payslip->id,
                        'pdf_path' => $payslip->pdf_path,
                    ]);
                }
            }
            $zip->close();
        }
        
        // Log::info('PayslipGeneratorService: Bulk download completed', [
        //     'payroll_id' => $payroll->id,
        //     'zip_file' => $zipFilename,
        //     'zip_size' => file_exists($zipFilename) ? filesize($zipFilename) : 0,
        //     'successful' => $successCount,
        //     'failed' => $failedCount,
        // ]);
        
        return $zipFilename;
    }
    
    public function cleanupTempFiles(string $zipPath): void
    {
        if (file_exists($zipPath)) {
            unlink($zipPath);
            // Log::info('PayslipGeneratorService: Temp file cleaned up', [
            //     'file_path' => $zipPath,
            // ]);
        }
    }
}