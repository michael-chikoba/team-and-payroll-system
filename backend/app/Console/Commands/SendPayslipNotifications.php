<?php

namespace App\Console\Commands;

use App\Jobs\SendPayslipEmail;
use App\Models\Payslip;
use Illuminate\Console\Command;

class SendPayslipNotifications extends Command
{
    protected $signature = 'payslip:send-notifications {--payroll-id=}';
    protected $description = 'Send payslip notifications to employees';

    public function handle(): int
    {
        $payrollId = $this->option('payroll-id');

        $query = Payslip::where('is_sent', false)
            ->whereHas('payroll', function ($query) {
                $query->where('status', 'completed');
            })
            ->with(['employee.user', 'payroll']);

        if ($payrollId) {
            $query->where('payroll_id', $payrollId);
        }

        $payslips = $query->get();

        if ($payslips->isEmpty()) {
            $this->info('No unsent payslips found.');
            return 0;
        }

        $this->info("Sending {$payslips->count()} payslip notifications...");

        $progressBar = $this->output->createProgressBar($payslips->count());
        $progressBar->start();

        foreach ($payslips as $payslip) {
            try {
                SendPayslipEmail::dispatch($payslip);
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->error("Failed to send payslip {$payslip->id}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->info("\nPayslip notifications queued successfully.");

        return 0;
    }
}