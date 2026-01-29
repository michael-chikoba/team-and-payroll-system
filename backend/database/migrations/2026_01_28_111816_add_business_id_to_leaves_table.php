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
        if (!Schema::hasColumn('leaves', 'business_id')) {
            Schema::table('leaves', function (Blueprint $table) {
                $table->foreignId('business_id')->nullable()->after('manager_id')->constrained('businesses')->onDelete('cascade');
                $table->index('business_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('leaves', 'business_id')) {
            Schema::table('leaves', function (Blueprint $table) {
                $table->dropForeign(['business_id']);
                $table->dropIndex(['business_id']);
                $table->dropColumn('business_id');
            });
        }
    }
};