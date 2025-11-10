<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use Illuminate\Console\Command;
use Carbon\Carbon;

class FixAttendanceData extends Command
{
    protected $signature = 'attendance:fix-data {--force : Force recalculation even if hours exist}';
    protected $description = 'Fix attendance data - clean up times and recalculate hours';

    public function handle()
    {
        $this->info('Starting attendance data cleanup...');

        $attendances = Attendance::all();
        $fixed = 0;
        $errors = 0;
        $force = $this->option('force');

        foreach ($attendances as $attendance) {
            try {
                $changed = false;

                // Fix clock_in if it's a full datetime
                if ($attendance->clock_in && $this->isDateTime($attendance->clock_in)) {
                    $attendance->clock_in = Carbon::parse($attendance->clock_in)->format('H:i:s');
                    $changed = true;
                    $this->line("Fixed clock_in for ID {$attendance->id}: {$attendance->clock_in}");
                }

                // Fix clock_out if it's a full datetime
                if ($attendance->clock_out && $this->isDateTime($attendance->clock_out)) {
                    $attendance->clock_out = Carbon::parse($attendance->clock_out)->format('H:i:s');
                    $changed = true;
                    $this->line("Fixed clock_out for ID {$attendance->id}: {$attendance->clock_out}");
                }

                // Recalculate total_hours if both times exist
                if ($attendance->clock_in && $attendance->clock_out) {
                    $newHours = $attendance->calculateTotalHours();
                    
                    // Force recalculation if --force flag is used OR if hours are missing/zero
                    if ($force || empty($attendance->total_hours) || $attendance->total_hours == 0) {
                        $attendance->total_hours = $newHours;
                        $changed = true;
                        $this->line("Recalculated hours for ID {$attendance->id}: {$newHours} hours (was: {$attendance->getOriginal('total_hours')})");
                    } elseif (abs($newHours - ($attendance->total_hours ?? 0)) > 0.01) {
                        $attendance->total_hours = $newHours;
                        $changed = true;
                        $this->line("Recalculated hours for ID {$attendance->id}: {$newHours} hours");
                    }
                }

                // Debug output for records with times but no hours
                if ($attendance->clock_in && $attendance->clock_out && empty($attendance->total_hours)) {
                    $this->warn("Record ID {$attendance->id} has times but no hours calculated:");
                    $this->line("  Date: {$attendance->date}");
                    $this->line("  Clock In: {$attendance->clock_in}");
                    $this->line("  Clock Out: {$attendance->clock_out}");
                    $this->line("  Current Hours: {$attendance->total_hours}");
                }

                if ($changed) {
                    $attendance->save();
                    $fixed++;
                }

            } catch (\Exception $e) {
                $this->error("Error processing attendance ID {$attendance->id}: " . $e->getMessage());
                $this->error("Stack trace: " . $e->getTraceAsString());
                $errors++;
            }
        }

        $this->info("\nCleanup complete!");
        $this->info("Total records processed: " . $attendances->count());
        $this->info("Records fixed: {$fixed}");
        $this->info("Errors: {$errors}");

        return 0;
    }

    private function isDateTime($time): bool
    {
        return strpos($time, ' ') !== false || 
               strpos($time, 'T') !== false || 
               strlen($time) > 8;
    }
}