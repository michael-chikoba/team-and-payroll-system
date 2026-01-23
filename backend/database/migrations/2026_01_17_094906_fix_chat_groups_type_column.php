<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Change to VARCHAR temporarily to avoid ENUM restrictions
        DB::statement("ALTER TABLE chat_groups MODIFY COLUMN type VARCHAR(50) NOT NULL");
        
        // Step 2: Clean up any invalid data - set everything to valid values
        DB::statement("
            UPDATE chat_groups 
            SET type = CASE 
                WHEN type = 'direct' THEN 'direct'
                WHEN type = 'department' THEN 'department'
                WHEN type = 'channel' OR is_channel = 1 THEN 'channel'
                ELSE 'group'
            END
        ");
        
        // Step 3: Now apply the new ENUM with all 4 values
        DB::statement("
            ALTER TABLE chat_groups 
            MODIFY COLUMN type ENUM('direct', 'group', 'channel', 'department') 
            NOT NULL DEFAULT 'group'
        ");
        
        // Step 4: Ensure is_channel is properly set
        DB::statement("
            UPDATE chat_groups 
            SET is_channel = CASE 
                WHEN type IN ('channel', 'department') THEN 1 
                ELSE 0 
            END
            WHERE is_channel IS NULL OR is_channel = ''
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Change to VARCHAR first
        DB::statement("ALTER TABLE chat_groups MODIFY COLUMN type VARCHAR(50) NOT NULL");
        
        // Then back to original ENUM (adjust if your original was different)
        DB::statement("ALTER TABLE chat_groups MODIFY COLUMN type ENUM('direct', 'department') NOT NULL");
    }
};