<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Get existing columns
        $columns = Schema::getColumnListing('sessions');
        
        Schema::table('sessions', function (Blueprint $table) use ($columns) {
            // Only add columns that don't exist
            if (!in_array('user_id', $columns)) {
                $table->foreignId('user_id')->nullable()->index()->after('id');
            }
            if (!in_array('ip_address', $columns)) {
                $table->string('ip_address', 45)->nullable();
            }
            if (!in_array('user_agent', $columns)) {
                $table->text('user_agent')->nullable();
            }
            if (!in_array('payload', $columns)) {
                $table->longText('payload');
            }
            if (!in_array('last_activity', $columns)) {
                $table->integer('last_activity')->index();
            }
        });
    }

    public function down(): void
    {
        // Rollback logic
        Schema::table('sessions', function (Blueprint $table) {
            $columns = Schema::getColumnListing('sessions');
            
            if (in_array('user_id', $columns)) {
                $table->dropColumn('user_id');
            }
        });
    }
};