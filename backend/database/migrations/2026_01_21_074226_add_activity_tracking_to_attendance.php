<?php
// database/migrations/2025_01_21_000000_add_activity_tracking_to_attendance.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('clock_out');
            $table->integer('idle_minutes')->default(0)->after('last_activity_at');
            $table->boolean('auto_clocked_out')->default(false)->after('idle_minutes');
            $table->text('activity_log')->nullable()->after('auto_clocked_out');
        });
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'last_activity_at',
                'idle_minutes',
                'auto_clocked_out',
                'activity_log'
            ]);
        });
    }
};