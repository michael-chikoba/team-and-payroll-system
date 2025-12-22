<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_user_id')->nullable()->after('assigned_to');
            $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['assigned_user_id']);
            $table->dropColumn('assigned_user_id');
        });
    }
};