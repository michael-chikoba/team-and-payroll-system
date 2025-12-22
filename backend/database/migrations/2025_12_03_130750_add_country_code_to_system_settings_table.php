<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->string('country_code')->nullable()->after('key');
            
            // Remove unique constraint from key and add composite unique
            $table->dropUnique(['key']);
            $table->unique(['key', 'country_code']);
        });

        // Update existing records to have 'global' as country_code
        \App\Models\SystemSetting::whereNull('country_code')->update(['country_code' => 'global']);
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropUnique(['key', 'country_code']);
            $table->dropColumn('country_code');
            $table->unique(['key']);
        });
    }
};