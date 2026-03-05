<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\Business;
use App\Services\EncryptionService;
use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;

class EncryptExistingData extends Command
{
    protected $signature = 'db:encrypt-existing 
        {--dry-run : Preview without saving}
        {--chunk=100 : Number of records to process at once}
        {--table= : Process only specific table (employee, payslip, business)}';

    protected $description = 'Encrypt existing plaintext sensitive data including salary fields';

    protected array $models = [
        Employee::class => [
            'phone' => 'string',
            'national_id' => 'string',
            'address' => 'string',
            'emergency_contact' => 'json',
            'bank_details' => 'json',
            // ADDED SALARY FIELDS
            'base_salary' => 'numeric',
            'transport_allowance' => 'numeric',
            'lunch_allowance' => 'numeric',
        ],
        Payslip::class => [
            'basic_salary' => 'numeric',
            'gross_salary' => 'numeric',
            'gross_pay' => 'numeric',
            'net_pay' => 'numeric',
            'total_deductions' => 'numeric',
            'tax_deductions' => 'numeric',
        ],
        Business::class => [
            'registration_number' => 'string',
            'tax_identification_number' => 'string',
            'phone' => 'string',
            'email' => 'string',
        ],
    ];

    public function handle(EncryptionService $encryption): int
    {
        $isDryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');
        $specificTable = $this->option('table');

        if ($isDryRun) {
            $this->warn('⚠️ DRY RUN MODE - No changes will be saved');
            $this->newLine();
        }

        $totalEncrypted = 0;
        $totalSkipped = 0;
        $totalErrors = 0;
        $processedTables = [];

        foreach ($this->models as $modelClass => $fields) {
            $model = new $modelClass;
            $table = $model->getTable();
            
            // Skip if specific table is requested and doesn't match
            if ($specificTable && !str_contains(strtolower($table), strtolower($specificTable))) {
                continue;
            }

            $processedTables[] = $table;
            
            $this->info("Processing table: {$table}");
            $this->info("Fields to encrypt: " . implode(', ', array_keys($fields)));
            $this->newLine();

            $count = $model->count();
            
            if ($count === 0) {
                $this->warn("No records found in {$table}");
                $this->newLine();
                continue;
            }

            $bar = $this->output->createProgressBar($count);
            $bar->start();

            $model::chunk($chunkSize, function ($records) use ($fields, $encryption, $isDryRun, &$totalEncrypted, &$totalSkipped, &$totalErrors, $bar, $table) {
                foreach ($records as $record) {
                    $updates = [];
                    $recordEncrypted = 0;
                    $recordSkipped = 0;

                    foreach ($fields as $field => $type) {
                        // Get the raw attribute value directly from the model's attributes array
                        $value = $record->getAttributes()[$field] ?? null;
                        
                        if ($value === null || $value === '') {
                            continue;
                        }

                        // Skip if already encrypted
                        if ($encryption->isEncrypted($value)) {
                            $recordSkipped++;
                            continue;
                        }

                        // Handle based on type
                        try {
                            if ($type === 'json') {
                                // If it's already an array/object, encode it
                                if (is_array($value) || is_object($value)) {
                                    $value = json_encode($value);
                                }
                                // Otherwise, assume it's already a JSON string
                            } elseif ($type === 'numeric') {
                                // Ensure numeric values are properly formatted
                                if (is_numeric($value)) {
                                    $value = (string) $value;
                                }
                            }

                            $encryptedValue = $encryption->encrypt($value);
                            
                            // Verify encryption was successful and value is different
                            if ($encryptedValue !== $value) {
                                $updates[$field] = $encryptedValue;
                                $recordEncrypted++;
                            }

                        } catch (\Exception $e) {
                            $totalErrors++;
                            $this->newLine();
                            $this->error("Failed to encrypt {$table}.{$field} for ID {$record->id}: {$e->getMessage()}");
                        }
                    }

                    if (!empty($updates)) {
                        $totalEncrypted += $recordEncrypted;
                        $totalSkipped += $recordSkipped;
                        
                        if (!$isDryRun) {
                            DB::table($table)
                                ->where('id', $record->id)
                                ->update($updates);
                                
                            $this->newLine();
                            $this->line("  ✓ Encrypted {$recordEncrypted} fields for {$table} ID {$record->id}" . 
                                        ($recordSkipped > 0 ? " ({$recordSkipped} already encrypted)" : ""));
                        } else {
                            $this->newLine();
                            $this->line("  🔷 Would encrypt {$recordEncrypted} fields for {$table} ID {$record->id}" . 
                                        ($recordSkipped > 0 ? " ({$recordSkipped} already encrypted)" : ""));
                            foreach (array_keys($updates) as $field) {
                                $this->line("      - {$field}");
                            }
                        }
                    }

                    $bar->advance();
                }
            });

            $bar->finish();
            $this->newLine(2);
        }

        if (empty($processedTables)) {
            $this->error("No matching tables found. Available tables: " . implode(', ', array_keys($this->models)));
            return Command::FAILURE;
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Fields encrypted', $totalEncrypted],
                ['Already encrypted (skipped)', $totalSkipped],
                ['Errors', $totalErrors],
            ]
        );

        if ($isDryRun) {
            $this->warn('DRY RUN completed. Run without --dry-run to apply changes.');
        } else {
            $this->info('Encryption completed successfully!');
        }

        return Command::SUCCESS;
    }
}