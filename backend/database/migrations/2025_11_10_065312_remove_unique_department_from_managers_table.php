<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('managers', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique('managers_department_unique');
            // Or if the constraint has a different name, use:
            // $table->dropUnique(['department']);
        });
    }

    public function down()
    {
        Schema::table('managers', function (Blueprint $table) {
            $table->unique('department');
        });
    }
};