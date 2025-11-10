<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Custom payroll commands
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