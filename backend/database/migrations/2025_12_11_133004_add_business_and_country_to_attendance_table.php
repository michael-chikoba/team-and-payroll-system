<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('attendances', function (Blueprint $table) {
        // Only add business_id if it doesn't exist
        if (!Schema::hasColumn('attendances', 'business_id')) {
            $table->foreignId('business_id')->nullable()->after('employee_id')->constrained('businesses')->onDelete('cascade');
        }

        // Only add country_id if it doesn't exist
        if (!Schema::hasColumn('attendances', 'country_id')) {
            $table->foreignId('country_id')->nullable()->after('business_id')->constrained('countries')->onDelete('cascade');
        }
    });
}
    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropForeign(['country_id']);
            $table->dropColumn(['business_id', 'country_id']);
        });
    }
};