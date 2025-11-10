<?php

namespace App\Console\Commands;

use App\Models\Payroll;
use App\Services\PayrollCalculationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessPayrollCommand extends Command
{
    protected $signature = 'payroll:process {--payroll-id=} {--force}';
    protected $description = 'Process payroll for a specific period';

    public function __construct(private PayrollCalculationService $payrollService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $payrollId = $this->option('payroll-id');
        $force = $this->option('force');

        if ($payrollId) {
            $payroll = Payroll::findOrFail($payrollId);
            return $this->processSinglePayroll($payroll, $force);
        }

        // Process pending payrolls
        $pendingPayrolls = Payroll::where('status', 'draft')->get();
        
        if ($pendingPayrolls->isEmpty()) {
            $this->info('No pending payrolls to process.');
            return 0;
        }

        foreach ($pendingPayrolls as $payroll) {
            $this->processSinglePayroll($payroll, $force);
        }

        return 0;
    }

    private function processSinglePayroll(Payroll $payroll, bool $force = false): int
    {
        $this->info("Processing payroll: {$payroll->payroll_period}");

        try {
            if (!$force && $payroll->status !== 'draft') {
                $this->warn("Payroll {$payroll->id} is not in draft status. Use --force to process anyway.");
                return 1;
            }

            $this->payrollService->processPayroll($payroll);
            $this->info("Successfully processed payroll: {$payroll->payroll_period}");

            return 0;
        } catch (\Exception $e) {
            Log::error("Failed to process payroll {$payroll->id}: " . $e->getMessage());
            $this->error("Failed to process payroll {$payroll->id}: " . $e->getMessage());
            
            $payroll->update(['status' => 'failed']);
            return 1;
        }
    }
}