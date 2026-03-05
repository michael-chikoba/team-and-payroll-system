<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\Business;
use App\Services\EncryptionService;
use Illuminate\Console\Command;

class DebugEncryption extends Command
{
    protected $signature = 'encryption:debug';
    protected $description = 'Debug encryption fields and values';

    protected EncryptionService $encryption;

    public function __construct(EncryptionService $encryption)
    {
        parent::__construct();
        $this->encryption = $encryption;
    }

    public function handle()
    {
        $this->info('🔍 ENCRYPTION DEBUG TOOL');
        $this->line('=======================');
        
        // Check Employee model
        $this->debugModel(Employee::class, 'employees');
        
        // Check Payslip model
        $this->debugModel(Payslip::class, 'payslips');
        
        // Check Business model
        $this->debugModel(Business::class, 'businesses');
        
        return Command::SUCCESS;
    }

    protected function debugModel(string $modelClass, string $table)
    {
        $this->newLine();
        $this->info("📊 Model: {$modelClass}");
        $this->line("Table: {$table}");
        
        $model = new $modelClass;
        $encryptedFields = $model->getEncryptedFields();
        
        $this->line("Encrypted fields defined: " . implode(', ', $encryptedFields));
        
        $count = $modelClass::count();
        $this->line("Total records: {$count}");
        
        if ($count > 0) {
            $sample = $modelClass::first();
            $this->line("\nSample record (ID: {$sample->id}):");
            
            foreach ($encryptedFields as $field) {
                $rawValue = $sample->getRawEncrypted($field);
                $this->line("  {$field}:");
                $this->line("    - Raw: " . ($rawValue ?? 'null'));
                $this->line("    - Type: " . gettype($rawValue));
                $this->line("    - Length: " . (is_string($rawValue) ? strlen($rawValue) : 'N/A'));
                $this->line("    - Is encrypted? " . ($rawValue ? ($this->encryption->isEncrypted($rawValue) ? '✅ Yes' : '❌ No') : 'N/A'));
            }
        }
    }
}