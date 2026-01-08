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
        // Add country_code column to businesses table
        if (!Schema::hasColumn('businesses', 'country_code')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->string('country_code', 2)->nullable()->after('country_id');
                $table->index('country_code');
            });
        }

        // Populate country_code based on existing country_id
        // You'll need to adjust this based on your countries table structure
        DB::statement("
            UPDATE businesses b
            INNER JOIN countries c ON b.country_id = c.id
            SET b.country_code = c.code
            WHERE b.country_code IS NULL
        ");

        // Or if you don't have a countries table with codes, use this:
        // DB::table('businesses')
        //     ->where('country_id', 1)
        //     ->whereNull('country_code')
        //     ->update(['country_code' => 'ZM']);
        //
        // DB::table('businesses')
        //     ->where('country_id', 3)
        //     ->whereNull('country_code')
        //     ->update(['country_code' => 'NA']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex(['country_code']);
            $table->dropColumn('country_code');
        });
    }
};