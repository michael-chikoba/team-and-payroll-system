<?php
// Migration file: database/migrations/xxxx_xx_xx_add_personal_fields_to_employees_table.php
// Run: php artisan make:migration add_personal_fields_to_employees_table --table=employees

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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('employment_type');
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->string('national_id')->nullable()->after('date_of_birth');
            $table->text('address')->nullable()->after('national_id');
            $table->string('emergency_contact')->nullable()->after('address');
            $table->string('profile_pic')->nullable()->after('emergency_contact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'date_of_birth',
                'national_id',
                'address',
                'emergency_contact',
                'profile_pic'
            ]);
        });
    }
};