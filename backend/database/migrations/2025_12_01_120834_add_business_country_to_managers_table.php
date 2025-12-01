<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('managers', function (Blueprint $table) {
            // Add foreign key columns
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('country_id')->nullable()->constrained()->onDelete('set null');
            
            // Add index for better performance
            $table->index(['business_id', 'country_id']);
        });
    }

    public function down(): void
    {
        Schema::table('managers', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropForeign(['country_id']);
            $table->dropColumn(['business_id', 'country_id']);
        });
    }
};