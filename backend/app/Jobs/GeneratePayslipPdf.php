<?php

namespace App\Jobs;

use App\Models\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class GeneratePayslipPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Payslip $payslip)
    {
    }

    public function handle(): void
    {
        try {
            $payslip = $this->payslip->load(['employee.user', 'payroll']);
            
            $pdf = PDF::loadView('pdf.payslip-template', compact('payslip'));
            
            $filename = "payslip-{$payslip->id}-{$payslip->payroll->payroll_period}.pdf";
            $path = "payslips/{$filename}";
            
            Storage::put($path, $pdf->output());
            
            $payslip->update(['pdf_path' => $path]);
            
        } catch (\Exception $e) {
            \Log::error("Failed to generate PDF for payslip {$this->payslip->id}: " . $e->getMessage());
            throw $e;
        }
    }
}