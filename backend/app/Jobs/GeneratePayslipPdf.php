<?php

namespace App\Jobs;

use App\Models\Payslip;
use App\Services\PayslipGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GeneratePayslipPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public Payslip $payslip) {}

    public function handle(PayslipGeneratorService $generator): void
    {
        Log::info('GeneratePayslipPdf: Job started', [
            'payslip_id' => $this->payslip->id,
        ]);

        try {
            // Delegate entirely to the service, which builds all required
            // template variables (currency_info, table_rows, company_name, etc.)
            // This is the same path used by direct/manual PDF generation,
            // ensuring all countries render identically.
            $path = $generator->generatePdf($this->payslip);

            Log::info('GeneratePayslipPdf: Job completed', [
                'payslip_id' => $this->payslip->id,
                'path'       => $path,
            ]);

        } catch (\Exception $e) {
            Log::error('GeneratePayslipPdf: Job failed', [
                'payslip_id' => $this->payslip->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw so the queue marks it as failed and retries
        }
    }
}
