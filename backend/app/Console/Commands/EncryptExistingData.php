<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\Business;
use App\Models\Payroll;
use App\Models\User;
use App\Services\EncryptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EncryptExistingData extends Command
{
    protected $signature = 'db:encrypt-existing 
        {--dry-run : Preview without saving}
        {--chunk=100 : Number of records to process at once}
        {--table= : Process only specific table (employee, payslip, business, payroll, session, user)}';

    protected $description = 'Encrypt existing plaintext sensitive data across all tables per security documentation';

    // Model-based tables (have Eloquent models)
    protected array $models = [
        Employee::class => [
            'phone'               => 'string',
            'national_id'         => 'string',
            'address'             => 'string',
            'emergency_contact'   => 'json',
            'bank_details'        => 'json',
            'base_salary'         => 'numeric',
            'transport_allowance' => 'numeric',
            'lunch_allowance'     => 'numeric',
        ],
        Payslip::class => [
            'basic_salary'        => 'numeric',
            'house_allowance'     => 'numeric',
            'transport_allowance' => 'numeric',
            'other_allowances'    => 'numeric',
            'overtime_rate'       => 'numeric',
            'overtime_pay'        => 'numeric',
            'gross_salary'        => 'numeric',
            'gross_pay'           => 'numeric',
            'tax_deductions'      => 'numeric',
            'napsa'               => 'numeric',
            'paye'                => 'numeric',
            'nhima'               => 'numeric',
            'pension'             => 'numeric',
            'other_deductions'    => 'numeric',
            'total_deductions'    => 'numeric',
            'net_pay'             => 'numeric',
            'status'              => 'string',
        ],
        Payroll::class => [
            'total_gross' => 'numeric',
            'total_net'   => 'numeric',
        ],
        Business::class => [
            'registration_number'       => 'string',
            'tax_identification_number' => 'string',
            'phone'                     => 'string',
            'email'                     => 'string',
        ],
       
    ];

    // Raw DB tables with no Eloquent model (e.g. Laravel built-in tables)
    protected array $rawTables = [
        'sessions' => [
            'primary_key' => 'id',
            'fields' => [
                'ip_address' => 'string',
            ],
        ],
    ];

    public function handle(EncryptionService $encryption): int
    {
        $isDryRun      = $this->option('dry-run');
        $chunkSize     = (int) $this->option('chunk');
        $specificTable = $this->option('table');

        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════╗');
        $this->line('║         ENCRYPTION MIGRATION — PAYROLL SYSTEM        ║');
        $this->line('╚══════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($isDryRun) {
            $this->warn('  ⚠️  DRY RUN MODE — No changes will be saved to the database');
            $this->newLine();
        }

        $totalEncrypted  = 0;
        $totalSkipped    = 0;
        $totalErrors     = 0;
        $processedTables = [];
        $tableResults    = [];

        // ─── Process Eloquent model tables ───────────────────────────────────
        foreach ($this->models as $modelClass => $fields) {

            try {
                $model = new $modelClass;
                $table = $model->getTable();
            } catch (\Exception $e) {
                $this->warn("  ⚠️  Could not load model {$modelClass} — skipping. ({$e->getMessage()})");
                continue;
            }

            if ($specificTable && !str_contains(strtolower($table), strtolower($specificTable))) {
                continue;
            }

            [$encrypted, $skipped, $errors] = $this->processModelTable(
                $model, $table, $fields, $encryption, $isDryRun, $chunkSize
            );

            $totalEncrypted += $encrypted;
            $totalSkipped   += $skipped;
            $totalErrors    += $errors;
            $processedTables[] = $table;
            $tableResults[]    = [$table, $encrypted, $skipped, $errors];
        }

        // ─── Process raw DB tables (no Eloquent model) ───────────────────────
        foreach ($this->rawTables as $table => $config) {

            if ($specificTable && !str_contains(strtolower($table), strtolower($specificTable))) {
                continue;
            }

            [$encrypted, $skipped, $errors] = $this->processRawTable(
                $table, $config, $encryption, $isDryRun, $chunkSize
            );

            $totalEncrypted += $encrypted;
            $totalSkipped   += $skipped;
            $totalErrors    += $errors;
            $processedTables[] = $table;
            $tableResults[]    = [$table, $encrypted, $skipped, $errors];
        }

        // ─── Nothing processed ────────────────────────────────────────────────
        if (empty($processedTables)) {
            $this->error('  No matching tables found.');
            $allTables = array_merge(
                array_map(fn($m) => (new $m)->getTable(), array_keys($this->models)),
                array_keys($this->rawTables)
            );
            $this->line('  Available: ' . implode(', ', $allTables));
            return Command::FAILURE;
        }

        // ─── Final summary ────────────────────────────────────────────────────
        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════╗');
        $this->line('║                   FINAL SUMMARY                      ║');
        $this->line('╚══════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->table(
            ['Table', 'Fields Encrypted', 'Already Encrypted (Skipped)', 'Errors'],
            $tableResults
        );

        $this->newLine();
        $this->table(
            ['Metric', 'Total'],
            [
                ['Total field values encrypted', $totalEncrypted],
                ['Total skipped (already encrypted)', $totalSkipped],
                ['Total errors', $totalErrors],
            ]
        );

        $this->newLine();

        if ($isDryRun) {
            $this->warn('  ⚠️  DRY RUN complete — no data was changed.');
            $this->line('  Run without --dry-run to apply:');
            $this->line('  <fg=cyan>php artisan db:encrypt-existing</>');
        } else {
            if ($totalErrors === 0) {
                $this->info('  ✅ All encryption completed successfully with no errors.');
            } else {
                $this->warn("  ⚠️  Completed with {$totalErrors} error(s). Review the output above.");
            }
        }

        $this->newLine();
        return Command::SUCCESS;
    }

    // ─── Eloquent model table processor ──────────────────────────────────────
    private function processModelTable(
        $model, string $table, array $fields,
        EncryptionService $encryption,
        bool $isDryRun, int $chunkSize
    ): array {
        $tableEncrypted = 0;
        $tableSkipped   = 0;
        $tableErrors    = 0;

        $this->printTableHeader($table, array_keys($fields));

        $count = $model->count();

        if ($count === 0) {
            $this->warn("  No records found in {$table} — skipping.");
            $this->newLine();
            return [0, 0, 0];
        }

        $this->line("  Total records: <fg=white>{$count}</>");
        $this->newLine();

        $bar = $this->makeProgressBar($count);
        $bar->start();

        $model::chunk($chunkSize, function ($records) use (
            $fields, $encryption, $isDryRun, $table, $bar,
            &$tableEncrypted, &$tableSkipped, &$tableErrors
        ) {
            foreach ($records as $record) {
                [$updates, $recordEncrypted, $recordSkipped, $recordErrors] =
                    $this->buildUpdates($record->getAttributes(), $record->id, $fields, $encryption, $table, $bar);

                $tableEncrypted += $recordEncrypted;
                $tableSkipped   += $recordSkipped;
                $tableErrors    += $recordErrors;

                if (!empty($updates) && !$isDryRun) {
                    DB::table($table)->where('id', $record->id)->update($updates);
                }

                $bar->advance();
            }
        });

        $this->finishTable($bar, $table, $tableEncrypted, $tableSkipped, $tableErrors, $isDryRun);
        return [$tableEncrypted, $tableSkipped, $tableErrors];
    }

    // ─── Raw DB table processor (no Eloquent model) ──────────────────────────
    private function processRawTable(
        string $table, array $config,
        EncryptionService $encryption,
        bool $isDryRun, int $chunkSize
    ): array {
        $fields     = $config['fields'];
        $primaryKey = $config['primary_key'];

        $tableEncrypted = 0;
        $tableSkipped   = 0;
        $tableErrors    = 0;

        $this->printTableHeader($table, array_keys($fields));

        $count = DB::table($table)->count();

        if ($count === 0) {
            $this->warn("  No records found in {$table} — skipping.");
            $this->newLine();
            return [0, 0, 0];
        }

        $this->line("  Total records: <fg=white>{$count}</>");
        $this->newLine();

        $bar = $this->makeProgressBar($count);
        $bar->start();

        DB::table($table)->orderBy($primaryKey)->chunk($chunkSize, function ($records) use (
            $fields, $encryption, $isDryRun, $table, $primaryKey, $bar,
            &$tableEncrypted, &$tableSkipped, &$tableErrors
        ) {
            foreach ($records as $record) {
                $attributes = (array) $record;
                $id         = $attributes[$primaryKey];

                [$updates, $recordEncrypted, $recordSkipped, $recordErrors] =
                    $this->buildUpdates($attributes, $id, $fields, $encryption, $table, $bar);

                $tableEncrypted += $recordEncrypted;
                $tableSkipped   += $recordSkipped;
                $tableErrors    += $recordErrors;

                if (!empty($updates) && !$isDryRun) {
                    DB::table($table)->where($primaryKey, $id)->update($updates);
                }

                $bar->advance();
            }
        });

        $this->finishTable($bar, $table, $tableEncrypted, $tableSkipped, $tableErrors, $isDryRun);
        return [$tableEncrypted, $tableSkipped, $tableErrors];
    }

    // ─── Shared: build the updates array for one record ──────────────────────
    private function buildUpdates(
        array $attributes, mixed $id,
        array $fields, EncryptionService $encryption,
        string $table, $bar
    ): array {
        $updates         = [];
        $recordEncrypted = 0;
        $recordSkipped   = 0;
        $recordErrors    = 0;

        foreach ($fields as $field => $type) {
            $value = $attributes[$field] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            if ($encryption->isEncrypted($value)) {
                $recordSkipped++;
                continue;
            }

            try {
                if ($type === 'json' && (is_array($value) || is_object($value))) {
                    $value = json_encode($value);
                } elseif ($type === 'numeric' && is_numeric($value)) {
                    $value = (string) $value;
                }

                $encryptedValue = $encryption->encrypt($value);

                if ($encryptedValue !== $value) {
                    $updates[$field] = $encryptedValue;
                    $recordEncrypted++;
                }

            } catch (\Exception $e) {
                $recordErrors++;
                $bar->clear();
                $this->error("  ✗ Failed: {$table}.{$field} ID {$id}: {$e->getMessage()}");
                $bar->display();
            }
        }

        if (!empty($updates)) {
            $bar->setMessage("ID {$id} — encrypting: " . implode(', ', array_keys($updates)));
        }

        return [$updates, $recordEncrypted, $recordSkipped, $recordErrors];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────
    private function printTableHeader(string $table, array $fieldNames): void
    {
        $this->line('┌─────────────────────────────────────────────────────');
        $this->line("│  Table : <fg=cyan>{$table}</>");
        $this->line('│  Fields: <fg=yellow>' . implode(', ', $fieldNames) . '</>');
        $this->line('└─────────────────────────────────────────────────────');
    }

    private function makeProgressBar(int $count)
    {
        $bar = $this->output->createProgressBar($count);
        $bar->setFormat('  %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('Starting...');
        return $bar;
    }

    private function finishTable($bar, string $table, int $encrypted, int $skipped, int $errors, bool $isDryRun): void
    {
        $bar->setMessage('Done.');
        $bar->finish();
        $this->newLine(2);

        $prefix = $isDryRun ? '  [DRY RUN] Would encrypt' : '  ✅ Encrypted';
        $this->line("{$prefix} <fg=green>{$encrypted}</> field values in <fg=cyan>{$table}</>");
        $this->line("  ⏭  Skipped (already encrypted): <fg=yellow>{$skipped}</>");
        if ($errors > 0) {
            $this->line("  ✗  Errors: <fg=red>{$errors}</>");
        }
        $this->newLine();
    }
}