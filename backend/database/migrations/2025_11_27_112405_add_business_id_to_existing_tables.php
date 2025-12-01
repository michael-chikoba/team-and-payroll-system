<?php
// database/migrations/2024_01_01_000002_add_business_id_to_existing_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add business_id to employees table
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('business_id')->after('id')->nullable()->constrained()->onDelete('cascade');
            $table->index(['business_id']);
        });

        // Add business_id to payrolls table
        Schema::table('payrolls', function (Blueprint $table) {
            $table->foreignId('business_id')->after('id')->nullable()->constrained()->onDelete('cascade');
            $table->index(['business_id']);
        });

        // Add current_business_id to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_business_id')->after('role')->nullable()->constrained('businesses')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropColumn('business_id');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropColumn('business_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_business_id']);
            $table->dropColumn('current_business_id');
        });
    }
};