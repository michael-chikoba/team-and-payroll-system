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
            // Add business_id column only if it doesn't exist
            if (!Schema::hasColumn('tax_configurations', 'business_id')) {
                $table->foreignId('business_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('businesses')
                    ->onDelete('cascade');
                
                // Add index for performance
                $table->index(['business_id', 'is_active']);
            }
        });
        
        // Update businesses table to track their active tax configuration
        Schema::table('businesses', function (Blueprint $table) {
            if (!Schema::hasColumn('businesses', 'tax_configuration_id')) {
                $table->foreignId('tax_configuration_id')
                    ->nullable()
                    ->constrained('tax_configurations')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (Schema::hasColumn('businesses', 'tax_configuration_id')) {
                $table->dropForeign(['tax_configuration_id']);
                $table->dropColumn('tax_configuration_id');
            }
        });
        
        Schema::table('tax_configurations', function (Blueprint $table) {
            if (Schema::hasColumn('tax_configurations', 'business_id')) {
                $table->dropForeign(['business_id']);
                $table->dropIndex(['business_id', 'is_active']);
                $table->dropColumn('business_id');
            }
        });
    }
};