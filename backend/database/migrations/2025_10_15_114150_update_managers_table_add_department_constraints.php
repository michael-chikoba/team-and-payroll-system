<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('managers', function (Blueprint $table) {
            // Make department unique and not nullable
            $table->string('department')->unique()->nullable(false)->change();

            // Add index for faster lookups
            $table->index('department');
        });
    }

    public function down(): void
    {
        Schema::table('managers', function (Blueprint $table) {
            // Revert back to original nullable column without unique
            $table->string('department')->nullable()->change();
            $table->dropIndex(['department']);
        });
    }
};
