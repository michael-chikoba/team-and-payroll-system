<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all existing configurations
        $configs = DB::table('tax_configurations')->get();

        foreach ($configs as $config) {
            // Decode the existing JSON
            $data = json_decode($config->config_data, true);
            
            // Skip if it already has the new structure or decode failed
            if (!$data || isset($data['statutory_deductions'])) {
                continue;
            }

            $newDeductions = [];

            // 1. Convert NAPSA (Old Format -> New Format)
            if (isset($data['napsaRate']) && $data['napsaRate'] > 0) {
                $newDeductions[] = [
                    'name' => 'NAPSA',
                    'type' => 'pension',
                    'base' => 'gross', // Standard NAPSA is on Gross
                    'employee_rate' => (float) $data['napsaRate'],
                    'employer_rate' => (float) $data['napsaRate'], // Usually matches employee
                    'ceiling' => isset($data['napsaMaxSalary']) ? (float) $data['napsaMaxSalary'] : 34164
                ];
            }

            // 2. Convert NHIMA (Old Format -> New Format)
            if (isset($data['nhimaEmployeeRate']) && $data['nhimaEmployeeRate'] > 0) {
                $newDeductions[] = [
                    'name' => 'NHIMA',
                    'type' => 'health',
                    'base' => 'basic', // Standard NHIMA is on Basic
                    'employee_rate' => (float) $data['nhimaEmployeeRate'],
                    'employer_rate' => (float) ($data['nhimaEmployerRate'] ?? $data['nhimaEmployeeRate']),
                    'ceiling' => isset($data['nhimaMaxSalary']) ? (float) $data['nhimaMaxSalary'] : null
                ];
            }

            // 3. Convert Generic Pension (if it exists)
            if (isset($data['pensionRate']) && $data['pensionRate'] > 0) {
                $newDeductions[] = [
                    'name' => 'Company Pension',
                    'type' => 'pension',
                    'base' => 'basic', // Private pension usually on basic
                    'employee_rate' => (float) $data['pensionRate'],
                    'employer_rate' => (float) $data['pensionRate'],
                    'ceiling' => isset($data['pensionMaxSalary']) ? (float) $data['pensionMaxSalary'] : null
                ];
            }

            // Add the new array to the config
            $data['statutory_deductions'] = $newDeductions;

            // CLEANUP: Remove the old hardcoded keys to keep JSON clean
            $keysToRemove = [
                'napsaRate', 'napsaMaxSalary', 
                'nhimaEmployeeRate', 'nhimaEmployerRate', 'nhimaMaxSalary',
                'pensionRate', 'pensionMaxSalary'
            ];

            foreach ($keysToRemove as $key) {
                unset($data[$key]);
            }

            // Update the record in the database
            DB::table('tax_configurations')
                ->where('id', $config->id)
                ->update(['config_data' => json_encode($data)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // It is difficult to reliably reverse dynamic arrays back to specific hardcoded fields
        // without data loss, so we leave this empty or perform a restore from backup.
    }
};