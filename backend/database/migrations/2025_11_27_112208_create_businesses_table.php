<?php
// database/migrations/2024_01_01_000000_create_businesses_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name');
            $table->string('registration_number')->unique()->nullable();
            $table->string('tax_identification_number')->nullable();
            $table->enum('business_type', ['sole_proprietorship', 'partnership', 'corporation', 'llc']);
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            
            // Contact Information
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            
            // Address
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('postal_code');
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            
            // Financial Details
            $table->string('currency_code', 3)->default('USD');
            $table->date('fiscal_year_start')->default('2024-01-01');
            $table->enum('pay_period', ['weekly', 'bi-weekly', 'semi-monthly', 'monthly'])->default('monthly');
            
            // Status
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->boolean('is_verified')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['country_id']);
            $table->index(['status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('businesses');
    }
};