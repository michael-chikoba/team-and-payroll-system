<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            // Add pension column after nhima if it doesn't exist
            if (!Schema::hasColumn('payslips', 'pension')) {
                $table->decimal('pension', 10, 2)->default(0)->after('nhima')
                    ->comment('Pension contribution (5% of basic salary for full-time employees)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            if (Schema::hasColumn('payslips', 'pension')) {
                $table->dropColumn('pension');
            }
        });
    }
};