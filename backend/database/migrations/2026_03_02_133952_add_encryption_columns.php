<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // EMPLOYEES TABLE
        Schema::table('employees', function (Blueprint $table) {
            $table->text('phone')->nullable()->change();
            $table->text('national_id')->nullable()->change();
            $table->text('address')->nullable()->change();
            $table->text('emergency_contact')->nullable()->change();
        });

        // PAYROLLS TABLE
        Schema::table('payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('payrolls', 'total_gross')) {
                $table->text('total_gross')->nullable()->change();
            }
            if (Schema::hasColumn('payrolls', 'total_net')) {
                $table->text('total_net')->nullable()->change();
            }
            if (Schema::hasColumn('payrolls', 'basic')) {
                $table->text('basic')->nullable()->change();
            }
        });

        // BUSINESSES TABLE
        Schema::table('businesses', function (Blueprint $table) {
            $this->dropIndexIfExists('businesses', 'businesses_registration_number_unique');
            $this->dropIndexIfExists('businesses', 'businesses_registration_number_index');
            
            $table->text('registration_number')->nullable()->change();
            $table->text('tax_identification_number')->nullable()->change();
            $table->text('email')->nullable()->change();
            $table->text('phone')->nullable()->change();
        });

        // USERS TABLE - Fix: Drop unique index first
        Schema::table('users', function (Blueprint $table) {
            // Drop the unique index on email
            $this->dropIndexIfExists('users', 'users_email_unique');
            
            // Now change the column type
            $table->text('email')->nullable()->change();
            
            // Re-create the unique index with a prefix length
            // MySQL requires prefix length for text columns in indexes
            DB::statement('ALTER TABLE `users` ADD UNIQUE INDEX `users_email_unique` (`email`(191))');
        });
    }

    public function down(): void
    {
        // Revert employees table
        Schema::table('employees', function (Blueprint $table) {
            $table->string('phone', 255)->nullable()->change();
            $table->string('national_id', 255)->nullable()->change();
            $table->text('address')->change();
            $table->string('emergency_contact', 255)->nullable()->change();
        });

        // Revert payrolls table
        Schema::table('payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('payrolls', 'total_gross')) {
                $table->decimal('total_gross', 12, 2)->nullable()->change();
            }
            if (Schema::hasColumn('payrolls', 'total_net')) {
                $table->decimal('total_net', 12, 2)->nullable()->change();
            }
            if (Schema::hasColumn('payrolls', 'basic')) {
                $table->decimal('basic', 10, 2)->nullable()->change();
            }
        });

        // Revert businesses table
        Schema::table('businesses', function (Blueprint $table) {
            $this->dropIndexIfExists('businesses', 'businesses_registration_number_unique');
            
            $table->string('registration_number', 255)->nullable()->unique()->change();
            $table->string('tax_identification_number', 255)->nullable()->change();
            $table->string('email', 255)->change();
            $table->string('phone', 255)->nullable()->change();
        });

        // Revert users table
        Schema::table('users', function (Blueprint $table) {
            $this->dropIndexIfExists('users', 'users_email_unique');
            
            $table->string('email', 255)->change();
            
            // Recreate original unique index
            $table->unique('email');
        });
    }

    /**
     * Helper function to drop an index if it exists
     */
    private function dropIndexIfExists(string $table, string $index): void
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($index) {
                $table->dropIndex($index);
            });
        } catch (\Exception $e) {
            // Log that index didn't exist but continue
            echo "Note: Index {$index} on table {$table} does not exist, skipping...\n";
        }
    }
};