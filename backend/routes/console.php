<?php
// routes/console.php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================================================
// Custom Payroll Commands
// ============================================================================

Artisan::command('payroll:process {cycle=monthly}', function ($cycle) {
    $this->info("Processing payroll for {$cycle} cycle...");
    Artisan::call('app:process-payroll', ['cycle' => $cycle]);
    $this->info('Payroll processing completed.');
})->describe('Process payroll for specified cycle');

Artisan::command('payroll:send-payslips', function () {
    $this->info('Sending payslip notifications...');
    Artisan::call('app:send-payslip-notifications');
    $this->info('Payslip notifications sent.');
})->describe('Send payslip email notifications');

Artisan::command('payroll:sync-attendance', function () {
    $this->info('Syncing attendance data...');
    // Add attendance sync logic here
    $this->info('Attendance data synced.');
})->describe('Sync attendance data from time tracking systems');

Artisan::command('payroll:update-leave-balances', function () {
    $this->info('Updating leave balances...');
    // Add leave balance update logic here
    $this->info('Leave balances updated.');
})->describe('Update employee leave balances for new year');

// ============================================================================
// Attendance & Overtime Commands
// ============================================================================

Artisan::command('attendance:fix-previous-day-overtime', function () {
    $this->call('attendance:fix-previous-day-overtime');
})->describe('Fix overtime sessions left open from previous days');

// ============================================================================
// Scheduled Jobs - MUST BE IN APP\CONSOLE\KERNEL.PHP, NOT HERE!
// ============================================================================
// ⚠️ IMPORTANT: Scheduled jobs should be defined in app/Console/Kernel.php
// The schedule method below is INCORRECT here - it belongs in Kernel.php