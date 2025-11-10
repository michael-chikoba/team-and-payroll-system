<?php
// database/migrations/2025_11_08_100150_update_leave_balances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get current columns
        $columns = Schema::getColumnListing('leave_balances');
        
        // Check if we already have the new columns
        $hasAllocatedDays = in_array('allocated_days', $columns);
        $hasUsedDays = in_array('used_days', $columns);
        $hasCarriedOver = in_array('carried_over', $columns);
        
        if ($hasAllocatedDays && $hasUsedDays && $hasCarriedOver) {
            echo "✓ Leave balances table already has required columns\n";
            return;
        }
        
        echo "Modifying leave_balances table...\n";
        
        // Modify type column from enum to string WITHOUT dropping constraints
        DB::statement('ALTER TABLE leave_balances MODIFY type VARCHAR(50) NOT NULL');
        
        // Add new columns if they don't exist
        if (!in_array('allocated_days', $columns)) {
            DB::statement('ALTER TABLE leave_balances ADD allocated_days DECIMAL(8,2) DEFAULT 0 AFTER type');
        }
        
        if (!in_array('used_days', $columns)) {
            DB::statement('ALTER TABLE leave_balances ADD used_days DECIMAL(8,2) DEFAULT 0 AFTER balance');
        }
        
        if (!in_array('carried_over', $columns)) {
            DB::statement('ALTER TABLE leave_balances ADD carried_over DECIMAL(8,2) DEFAULT 0 AFTER used_days');
        }
        
        // Migrate existing balance data to new structure
        DB::statement('
            UPDATE leave_balances 
            SET allocated_days = balance,
                used_days = 0,
                carried_over = 0
            WHERE allocated_days = 0
        ');
        
        // Also update balance column to DECIMAL(8,2) for consistency
        DB::statement('ALTER TABLE leave_balances MODIFY balance DECIMAL(8,2) DEFAULT 0');
        
        echo "✓ Leave balances table updated successfully\n";
    }

    public function down(): void
    {
        $columns = Schema::getColumnListing('leave_balances');
        
        if (in_array('allocated_days', $columns)) {
            DB::statement('ALTER TABLE leave_balances DROP COLUMN allocated_days');
        }
        if (in_array('used_days', $columns)) {
            DB::statement('ALTER TABLE leave_balances DROP COLUMN used_days');
        }
        if (in_array('carried_over', $columns)) {
            DB::statement('ALTER TABLE leave_balances DROP COLUMN carried_over');
        }
    }
};