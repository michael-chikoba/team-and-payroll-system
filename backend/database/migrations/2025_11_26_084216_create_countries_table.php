<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique()->comment('ISO 3166-1 alpha-2 code');
            $table->string('name', 100);
            $table->string('currency_code', 3)->comment('ISO 4217 currency code');
            $table->string('currency_symbol', 10);
            $table->string('date_format', 20)->default('Y-m-d');
            $table->string('time_format', 20)->default('H:i:s');
            $table->string('timezone', 50);
            $table->string('phone_code', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};