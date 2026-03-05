<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\Business;
use App\Services\EncryptionService; // Fix namespace
use Illuminate\Console\Command;

class TestEncryption extends Command
{
    protected $signature = 'encryption:test {id?}';
    protected $description = 'Test encryption on models';

    protected EncryptionService $encryption;

    public function __construct(EncryptionService $encryption)
    {
        parent::__construct();
        $this->encryption = $encryption;
    }

    public function handle()
    {
        $id = $this->argument('id');
        
        $this->info('Testing Employee Encryption');
        $this->line('===========================');
        
        $query = Employee::with(['user', 'business']);
        if ($id) {
            $query->where('id', $id);
        }
        
        $employees = $query->take(5)->get();
        
        foreach ($employees as $employee) {
            $this->line("\nEmployee ID: {$employee->id} - {$employee->full_name}");
            $rawPhone = $employee->getRawEncrypted('phone');
            $this->line("Raw encrypted phone: " . ($rawPhone ?: 'null'));
            $this->line("Is encrypted? " . ($rawPhone ? ($this->encryption->isEncrypted($rawPhone) ? 'Yes' : 'No') : 'N/A'));
            $this->line("Decrypted phone: " . ($employee->phone ?: 'null'));
            
            $rawNationalId = $employee->getRawEncrypted('national_id');
            $this->line("Raw encrypted national_id: " . ($rawNationalId ?: 'null'));
            $this->line("Is encrypted? " . ($rawNationalId ? ($this->encryption->isEncrypted($rawNationalId) ? 'Yes' : 'No') : 'N/A'));
            $this->line("Decrypted national_id: " . ($employee->national_id ?: 'null'));
            
            $this->line('---');
        }
        
        $this->newLine();
        $this->info('Testing Payslip Encryption');
        $this->line('==========================');
        
        $payslips = Payslip::with('employee')->take(5)->get();
        
        foreach ($payslips as $payslip) {
            $this->line("\nPayslip ID: {$payslip->id} - Employee: {$payslip->employee?->full_name}");
            
            $rawBasicSalary = $payslip->getRawEncrypted('basic_salary');
            $this->line("Raw encrypted basic_salary: " . ($rawBasicSalary ?: 'null'));
            $this->line("Is encrypted? " . ($rawBasicSalary ? ($this->encryption->isEncrypted($rawBasicSalary) ? 'Yes' : 'No') : 'N/A'));
            $this->line("Decrypted basic_salary: " . ($payslip->basic_salary ?: 'null'));
            
            $rawGrossPay = $payslip->getRawEncrypted('gross_pay');
            $this->line("Raw encrypted gross_pay: " . ($rawGrossPay ?: 'null'));
            $this->line("Is encrypted? " . ($rawGrossPay ? ($this->encryption->isEncrypted($rawGrossPay) ? 'Yes' : 'No') : 'N/A'));
            $this->line("Decrypted gross_pay: " . ($payslip->gross_pay ?: 'null'));
            
            $rawNetPay = $payslip->getRawEncrypted('net_pay');
            $this->line("Raw encrypted net_pay: " . ($rawNetPay ?: 'null'));
            $this->line("Is encrypted? " . ($rawNetPay ? ($this->encryption->isEncrypted($rawNetPay) ? 'Yes' : 'No') : 'N/A'));
            $this->line("Decrypted net_pay: " . ($payslip->net_pay ?: 'null'));
            
            $this->line('---');
        }
        
        $this->newLine();
        $this->info('Testing Business Encryption');
        $this->line('==========================');
        
        $businesses = Business::take(5)->get();
        
        foreach ($businesses as $business) {
            $this->line("\nBusiness ID: {$business->id} - {$business->name}");
            
            $rawRegNumber = $business->getRawEncrypted('registration_number');
            $this->line("Raw encrypted registration_number: " . ($rawRegNumber ?: 'null'));
            $this->line("Is encrypted? " . ($rawRegNumber ? ($this->encryption->isEncrypted($rawRegNumber) ? 'Yes' : 'No') : 'N/A'));
            $this->line("Decrypted registration_number: " . ($business->registration_number ?: 'null'));
            
            $rawTin = $business->getRawEncrypted('tax_identification_number');
            $this->line("Raw encrypted tax_identification_number: " . ($rawTin ?: 'null'));
            $this->line("Is encrypted? " . ($rawTin ? ($this->encryption->isEncrypted($rawTin) ? 'Yes' : 'No') : 'N/A'));
            $this->line("Decrypted tax_identification_number: " . ($business->tax_identification_number ?: 'null'));
            
            $this->line('---');
        }
        
        return Command::SUCCESS;
    }
}