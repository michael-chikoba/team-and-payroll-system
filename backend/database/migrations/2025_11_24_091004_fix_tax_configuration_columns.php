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
    Schema::table('tax_configurations', function (Blueprint $table) {
        // Remove old columns if they exist
        if (Schema::hasColumn('tax_configurations', 'tax_brackets')) {
            $table->dropColumn('tax_brackets');
        }
        if (Schema::hasColumn('tax_configurations', 'social_security_rate')) {
            $table->dropColumn('social_security_rate');
        }
        if (Schema::hasColumn('tax_configurations', 'medicare_rate')) {
            $table->dropColumn('medicare_rate');
        }

        // Add the correct config_data column
        if (!Schema::hasColumn('tax_configurations', 'config_data')) {
            $table->json('config_data')->nullable();
        }
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
