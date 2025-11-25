<?php
namespace App\Console\Commands;

use App\Models\Attendance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FixAttendanceHours extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'attendance:fix-hours 
                            {--employee-id= : Specific employee ID to fix}
                            {--date= : Specific date (YYYY-MM-DD) to fix}
                            {--month= : Specific month (1-12) to fix}
                            {--year= : Specific year to fix}
                            {--all : Fix all records}
                            {--dry-run : Show what would be updated without saving}';

    /**
     * The console command description.
     */
    protected $description = 'Recalculate and fix total hours for attendance records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting attendance hours fix...');
        
        $employeeId = $this->option('employee-id');
        $date = $this->option('date');
        $month = $this->option('month');
        $year = $this->option('year');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be saved');
        }

        // Build query
        $query = Attendance::whereNotNull('clock_in')
            ->whereNotNull('clock_out')
            ->where(function($q) {
                $q->whereNull('total_hours')
                  ->orWhere('total_hours', 0)
                  ->orWhere('total_hours', '0.00');
            });

        // Apply filters
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
            $this->info("Filtering by employee ID: {$employeeId}");
        }

        if ($date) {
            $query->whereDate('date', $date);
            $this->info("Filtering by date: {$date}");
        }

        if ($month && $year) {
            $query->whereMonth('date', $month)
                  ->whereYear('date', $year);
            $this->info("Filtering by month: {$month}/{$year}");
        } elseif ($year) {
            $query->whereYear('date', $year);
            $this->info("Filtering by year: {$year}");
        }

        if (!$all && !$employeeId && !$date && !$month) {
            $this->error('Please specify --all or use filters (--employee-id, --date, --month, --year)');
            return 1;
        }

        $records = $query->get();
        $total = $records->count();

        if ($total === 0) {
            $this->info('No records need updating!');
            return 0;
        }

        $this->info("Found {$total} records to update");

        // Show preview
        if ($dryRun || $this->confirm('Show preview of first 5 records?', true)) {
            $this->table(
                ['ID', 'Employee', 'Date', 'Clock In', 'Clock Out', 'Current Hours', 'New Hours'],
                $records->take(5)->map(function($record) {
                    return [
                        $record->id,
                        $record->employee->first_name ?? 'N/A',
                        $record->date,
                        $record->clock_in,
                        $record->clock_out,
                        $record->total_hours ?? 0,
                        $record->calculateTotalHours()
                    ];
                })
            );
        }

        if ($dryRun) {
            $this->info('Dry run complete. No changes were made.');
            return 0;
        }

        if (!$this->confirm("Proceed with updating {$total} records?", true)) {
            $this->warn('Operation cancelled');
            return 0;
        }

        // Process records with progress bar
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        $errors = 0;

        foreach ($records as $record) {
            try {
                $oldHours = $record->total_hours;
                $record->total_hours = $record->calculateTotalHours();
                
                if ($record->save()) {
                    $updated++;
                    Log::info('Fixed attendance hours', [
                        'attendance_id' => $record->id,
                        'employee_id' => $record->employee_id,
                        'date' => $record->date,
                        'old_hours' => $oldHours,
                        'new_hours' => $record->total_hours
                    ]);
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('Error fixing attendance hours', [
                    'attendance_id' => $record->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info("✅ Successfully updated: {$updated} records");
        if ($errors > 0) {
            $this->error("❌ Errors: {$errors} records");
        }

        return 0;
    }
}