<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ActivityTrackingService;

class CheckIdleOvertimeSessions extends Command
{
    protected $signature   = 'sessions:check-idle';
    protected $description = 'Check all open overtime sessions for idle activity, issue warnings, and auto-close stale sessions.';

    public function handle(ActivityTrackingService $service): int
    {
        $this->info('──────────────────────────────────────────');
        $this->info(' Checking idle overtime sessions...');
        $this->info('──────────────────────────────────────────');

        try {
            $result = $service->checkIdleSessions();
        } catch (\Throwable $e) {
            $this->error('Fatal error running checkIdleSessions(): ' . $e->getMessage());
            return Command::FAILURE;
        }

        // ── Summary ────────────────────────────────────────────────────────
        // Keys returned by ActivityTrackingService::checkIdleSessions():
        //   total_checked, warnings_sent, sessions_closed, still_active, pending_close, details[]
        $this->info(sprintf(
            'Total checked: %d | Warnings sent: %d | Pending close: %d | Sessions closed: %d | Still active: %d',
            $result['total_checked']   ?? 0,
            $result['warnings_sent']   ?? 0,
            $result['pending_close']   ?? 0,
            $result['sessions_closed'] ?? 0,
            $result['still_active']    ?? 0,
        ));

        // ── Detail table ───────────────────────────────────────────────────
        if (!empty($result['details'])) {
            $rows = array_map(function (array $detail): array {
                $action = match ($detail['action'] ?? 'unknown') {
                    'active'        => '<fg=green>active</>',
                    'warned'        => '<fg=yellow>warning sent</>',
                    'closed'        => '<fg=red>auto-closed</>',
                    'pending_close' => '<fg=yellow>pending close</>',
                    default         => $detail['action'] ?? '—',
                };

                return [
                    $detail['attendance_id'] ?? '—',
                    $detail['employee_id']   ?? '—',
                    $action,
                    round($detail['idle_minutes'] ?? 0, 1) . ' min',
                    isset($detail['seconds_remaining'])
                        ? round($detail['seconds_remaining']) . 's until close'
                        : ($detail['message'] ?? '—'),
                ];
            }, $result['details']);

            $this->table(
                ['Attendance ID', 'Employee ID', 'Action', 'Idle', 'Note'],
                $rows
            );
        } else {
            $this->line('No open overtime sessions found.');
        }

        // ── Exit with failure if any sessions errored ──────────────────────
        $hasErrors = !empty(array_filter(
            $result['details'] ?? [],
            fn(array $d) => ($d['action'] ?? '') === 'error'
        ));

        if ($hasErrors) {
            $this->warn('Some sessions encountered errors — check the Laravel log for details.');
            return Command::FAILURE;
        }

        $this->info('Done.');
        return Command::SUCCESS;
    }
}