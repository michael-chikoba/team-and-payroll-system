<?php
// Migration file: database/migrations/xxxx_xx_xx_create_documents_table.php
// Run: php artisan make:migration create_documents_table

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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('type')->nullable(); // e.g., 'resume', 'certification', 'general'
            $table->unsignedBigInteger('size')->nullable(); // File size in bytes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};