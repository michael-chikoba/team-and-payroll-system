<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Tasks table already has the required columns, just add group columns
            $table->foreignId('business_group_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('business_groups')
                  ->nullOnDelete();
            $table->boolean('is_group_task')
                  ->default(false)
                  ->after('business_group_id');
            $table->foreignId('assigned_business_id')
                  ->nullable()
                  ->after('assigned_to')
                  ->constrained('businesses')
                  ->nullOnDelete();
            
            $table->index('business_group_id');
            $table->index('assigned_business_id');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['business_group_id']);
            $table->dropForeign(['assigned_business_id']);
            $table->dropColumn(['business_group_id', 'is_group_task', 'assigned_business_id']);
        });
    }
};