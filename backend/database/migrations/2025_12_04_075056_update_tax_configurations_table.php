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
            // Add business_id column if it doesn't exist (for business-specific configs)
            if (!Schema::hasColumn('tax_configurations', 'business_id')) {
                $table->foreignId('business_id')
                      ->nullable()
                      ->after('id')
                      ->constrained('businesses')
                      ->onDelete('cascade')
                      ->comment('Business-specific tax configuration (null for global)');
            }
            
            // Add country_code column if it doesn't exist
            if (!Schema::hasColumn('tax_configurations', 'country_code')) {
                $table->string('country_code', 10)
                      ->nullable()
                      ->after('business_id')
                      ->comment('ISO country code (e.g., ZM for Zambia)');
                
                // Add foreign key to countries table
                $table->foreign('country_code')
                      ->references('code')
                      ->on('countries')
                      ->onDelete('cascade');
            }
            
            // Rename 'country' to 'country_name' if 'country' exists
            if (Schema::hasColumn('tax_configurations', 'country') && 
                !Schema::hasColumn('tax_configurations', 'country_name')) {
                $table->renameColumn('country', 'country_name');
            }
            
            // Add indexes for better query performance
            if (!Schema::hasColumn('tax_configurations', 'country_code')) {
                $table->index(['country_code', 'is_active'], 'idx_country_active');
                $table->index(['business_id', 'is_active'], 'idx_business_active');
            }
        });
        
        // Update existing records to use country_code
        DB::statement("
            UPDATE tax_configurations tc
            JOIN countries c ON tc.country_name = c.name
            SET tc.country_code = c.code
            WHERE tc.country_code IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_configurations', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_country_active');
            $table->dropIndex('idx_business_active');
            
            // Drop foreign keys
            $table->dropForeign(['business_id']);
            $table->dropForeign(['country_code']);
            
            // Drop columns
            $table->dropColumn(['business_id', 'country_code']);
            
            // Rename country_name back to country if it was renamed
            if (Schema::hasColumn('tax_configurations', 'country_name')) {
                $table->renameColumn('country_name', 'country');
            }
        });
    }
};