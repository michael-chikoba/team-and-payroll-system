<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // last_activity_at already exists — skip it, only add the two new columns
            if (!Schema::hasColumn('attendances', 'idle_warned_at')) {
                $table->timestamp('idle_warned_at')->nullable()->after('last_activity_at');
            }

            if (!Schema::hasColumn('attendances', 'idle_warning_sent')) {
                $table->boolean('idle_warning_sent')->default(false)->after('idle_warned_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('attendances', 'idle_warned_at')    ? 'idle_warned_at'    : null,
                Schema::hasColumn('attendances', 'idle_warning_sent') ? 'idle_warning_sent' : null,
            ]));
        });
    }
};