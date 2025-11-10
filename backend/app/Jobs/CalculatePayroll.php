<?php

namespace App\Jobs;

use App\Models\Payroll;
use App\Services\PayrollCalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculatePayroll implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Payroll $payroll)
    {
    }

    public function handle(PayrollCalculationService $payrollService): void
    {
        try {
            Log::info("Starting payroll calculation for payroll ID: {$this->payroll->id}");
            
            $payrollService->processPayroll($this->payroll);
            
            Log::info("Completed payroll calculation for payroll ID: {$this->payroll->id}");
            
        } catch (\Exception $e) {
            Log::error("Payroll calculation failed for payroll ID: {$this->payroll->id}. Error: " . $e->getMessage());
            
            $this->payroll->update([
                'status' => 'failed',
                'processed_at' => now(),
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Payroll calculation job failed for payroll ID: {$this->payroll->id}. Error: " . $exception->getMessage());
        
        $this->payroll->update([
            'status' => 'failed',
            'processed_at' => now(),
        ]);
    }
}