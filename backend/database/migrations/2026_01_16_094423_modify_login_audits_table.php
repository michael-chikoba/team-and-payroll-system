<?php
// Create a new migration: php artisan make:migration modify_login_audits_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('login_audits', function (Blueprint $table) {

        // 1. Drop existing foreign key
        $table->dropForeign(['user_id']);

        // 2. Make user_id nullable
        $table->unsignedBigInteger('user_id')->nullable()->change();

        // 3. Re-add foreign key
        $table->foreign('user_id')
              ->references('id')
              ->on('users')
              ->onDelete('cascade');

        // 4. Add email column only if it does not exist
        if (!Schema::hasColumn('login_audits', 'email')) {
            $table->string('email')->after('user_id');
        }

        // 5. Extra fields (safe-guarded)
        if (!Schema::hasColumn('login_audits', 'device_type')) {
            $table->string('device_type', 20)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('platform', 50)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('country_code', 2)->nullable()->index();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('timezone', 50)->nullable();
            $table->string('isp', 100)->nullable();
            $table->enum('status', ['success', 'failed'])->default('success')->index();
            $table->string('failure_reason')->nullable();
            $table->timestamp('logout_at')->nullable();
        }
    });
}

    public function down(): void
    {
        Schema::table('login_audits', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            // Remove other columns if needed
        });
    }
};