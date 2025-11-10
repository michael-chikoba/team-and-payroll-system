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
        // 2024_01_11_000000_create_tax_configurations_table.php
Schema::create('tax_configurations', function (Blueprint $table) {
    $table->id();
    $table->string('country');
    $table->string('state')->nullable();
    $table->json('tax_brackets'); // JSON of tax brackets
    $table->decimal('social_security_rate', 5, 2)->default(0);
    $table->decimal('medicare_rate', 5, 2)->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_configurations');
    }
};
