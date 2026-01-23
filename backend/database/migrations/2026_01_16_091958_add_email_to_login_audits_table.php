<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_audits', function (Blueprint $table) {
            $table->string('email')->after('user_id')->index();
            $table->string('device_type', 20)->nullable()->after('user_agent');
            $table->string('browser', 50)->nullable()->after('device_type');
            $table->string('platform', 50)->nullable()->after('browser');
            $table->string('country', 100)->nullable()->after('platform');
            $table->string('country_code', 2)->nullable()->index()->after('country');
            $table->string('region', 100)->nullable()->after('country_code');
            $table->string('city', 100)->nullable()->after('region');
            $table->string('postal_code', 20)->nullable()->after('city');
            $table->string('timezone', 50)->nullable()->after('longitude');
            $table->string('isp', 100)->nullable()->after('timezone');
            $table->enum('status', ['success', 'failed'])->default('success')->index()->after('isp');
            $table->string('failure_reason')->nullable()->after('status');
            $table->timestamp('logout_at')->nullable()->after('login_at');
        });
    }

    public function down(): void
    {
        Schema::table('login_audits', function (Blueprint $table) {
            $table->dropColumn([
                'email', 'device_type', 'browser', 'platform',
                'country', 'country_code', 'region', 'city', 
                'postal_code', 'timezone', 'isp', 
                'status', 'failure_reason', 'logout_at'
            ]);
        });
    }
};