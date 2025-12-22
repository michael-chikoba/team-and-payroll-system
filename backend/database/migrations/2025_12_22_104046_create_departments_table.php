<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedBigInteger('business_id')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
                
                // If you have a businesses table, you can enable this:
                 $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};