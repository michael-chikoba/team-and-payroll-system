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
        Schema::table('employees', function (Blueprint $table) {
            // Add business_id column (nullable)
            if (!Schema::hasColumn('employees', 'business_id')) {
                $table->unsignedBigInteger('business_id')->nullable()->after('country_id');
                $table->index('business_id');
                
                // Add foreign key constraint
                $table->foreign('business_id')
                      ->references('id')
                      ->on('businesses')
                      ->onDelete('cascade');
            }
            
            // Make country_id nullable if it exists and is not nullable
            if (Schema::hasColumn('employees', 'country_id')) {
                $table->unsignedBigInteger('country_id')->nullable()->change();
            } else {
                // Add country_id if it doesn't exist
                $table->unsignedBigInteger('country_id')->nullable()->after('id');
                $table->index('country_id');
                
                // Add foreign key constraint
                $table->foreign('country_id')
                      ->references('id')
                      ->on('countries')
                      ->onDelete('restrict');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Drop business_id
            if (Schema::hasColumn('employees', 'business_id')) {
                $table->dropForeign(['business_id']);
                $table->dropIndex(['business_id']);
                $table->dropColumn('business_id');
            }
            
            // Note: We're not dropping country_id as it might have been added
            // by a previous migration. If you want to drop it, uncomment below:
            /*
            if (Schema::hasColumn('employees', 'country_id')) {
                $table->dropForeign(['country_id']);
                $table->dropIndex(['country_id']);
                $table->dropColumn('country_id');
            }
            */
        });
    }
};