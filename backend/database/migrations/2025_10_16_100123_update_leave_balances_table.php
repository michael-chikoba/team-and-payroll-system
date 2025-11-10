<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (!Schema::hasColumn('leave_balances', 'allocated_days')) {
                $table->decimal('allocated_days', 8, 2)->default(0)->after('balance');
            }
            
            if (!Schema::hasColumn('leave_balances', 'used_days')) {
                $table->decimal('used_days', 8, 2)->default(0)->after('allocated_days');
            }
            
            if (!Schema::hasColumn('leave_balances', 'carried_over')) {
                $table->decimal('carried_over', 8, 2)->default(0)->after('used_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropColumn(['allocated_days', 'used_days', 'carried_over']);
        });
    }
};