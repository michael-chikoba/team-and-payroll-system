<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetPayslipEncryption extends Command
{
    protected $signature = 'encryption:reset-payslips';
    protected $description = 'Reset payslip fields to original values';

    public function handle()
    {
        $this->warn('This will reset all payslip fields to their original values!');
        $this->warn('Make sure you have a backup before proceeding.');
        
        if (!$this->confirm('Do you wish to continue?')) {
            return Command::FAILURE;
        }

        // Get original values from backup or set to 0
        // You'll need to adjust these based on your actual data
        DB::table('payslips')->update([
            'basic_salary' => DB::raw('0.00'),
            'gross_salary' => DB::raw('0.00'),
            'gross_pay' => DB::raw('0.00'),
            'net_pay' => DB::raw('0.00'),
            'total_deductions' => DB::raw('0.00'),
            'tax_deductions' => DB::raw('0.00'),
        ]);

        $this->info('Payslip fields have been reset.');
        return Command::SUCCESS;
    }
}