<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add business_id to system_settings table
        Schema::table('system_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable()->after('id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            
            // Update unique constraint to include business_id
            $table->dropUnique(['key', 'country_code']);
            $table->unique(['key', 'country_code', 'business_id'], 'settings_unique_key');
        });

        // Migrate existing data
        // Country-specific settings become templates
        // We'll keep them for reference but mark them as templates
        DB::table('system_settings')
            ->where('country_code', '!=', 'global')
            ->whereNull('country_code')
            ->update(['description' => DB::raw("CONCAT('[TEMPLATE] ', description)")]);
    }

    public function down()
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropUnique('settings_unique_key');
            $table->dropColumn('business_id');
            
            // Restore original unique constraint
            $table->unique(['key', 'country_code']);
        });
    }
};