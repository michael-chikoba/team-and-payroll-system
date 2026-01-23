<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Modify the status column to include all status values
            $table->enum('status', [
                'draft', 
                'pending', 
                'in_review', 
                'approved', 
                'rejected', 
                'in_progress',
                'on_hold',
                'resolved',
                'closed',
                'reopened'
            ])->default('draft')->change();
        });
    }

    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Revert to original enum values if needed
            $table->enum('status', [
                'pending', 
                'approved', 
                'rejected', 
                'in_progress'
            ])->default('pending')->change();
        });
    }
};