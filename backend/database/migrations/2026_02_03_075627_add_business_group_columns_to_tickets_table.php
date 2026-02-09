<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Check and add business_id first if it doesn't exist
            if (!Schema::hasColumn('tickets', 'business_id')) {
                $table->foreignId('business_id')
                      ->nullable()
                      ->after('id')
                      ->constrained()
                      ->nullOnDelete();
            }
            
            // Now add the business group columns
            $table->foreignId('business_group_id')
                  ->nullable()
                  ->after('business_id')
                  ->constrained('business_groups')
                  ->nullOnDelete();
            $table->boolean('is_group_ticket')
                  ->default(false)
                  ->after('business_group_id');
            $table->foreignId('assigned_business_id')
                  ->nullable()
                  ->after('is_group_ticket')
                  ->constrained('businesses')
                  ->nullOnDelete();
            
            $table->index('business_group_id');
            $table->index('assigned_business_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['business_group_id']);
            $table->dropForeign(['assigned_business_id']);
            $table->dropColumn(['business_group_id', 'is_group_ticket', 'assigned_business_id']);
            
            // Optionally drop business_id if it was added by this migration
            // Uncomment if you want to remove it on rollback
            // if (Schema::hasColumn('tickets', 'business_id')) {
            //     $table->dropForeign(['business_id']);
            //     $table->dropColumn('business_id');
            // }
        });
    }
};