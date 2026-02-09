<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add is_superadmin column to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_superadmin')) {
                $table->boolean('is_superadmin')->default(false)->after('role');
                $table->index('is_superadmin');
            }
        });

        // Add business limits and subscription fields to businesses table
        Schema::table('businesses', function (Blueprint $table) {
            // Subscription and limits
            if (!Schema::hasColumn('businesses', 'employee_limit')) {
                $table->integer('employee_limit')->default(50)->after('status');
            }
            if (!Schema::hasColumn('businesses', 'current_employee_count')) {
                $table->integer('current_employee_count')->default(0)->after('employee_limit');
            }
            if (!Schema::hasColumn('businesses', 'subscription_tier')) {
                $table->string('subscription_tier')->default('basic')->after('current_employee_count');
            }
            if (!Schema::hasColumn('businesses', 'subscription_start_date')) {
                $table->date('subscription_start_date')->nullable()->after('subscription_tier');
            }
            if (!Schema::hasColumn('businesses', 'subscription_end_date')) {
                $table->date('subscription_end_date')->nullable()->after('subscription_start_date');
            }
            if (!Schema::hasColumn('businesses', 'is_trial')) {
                $table->boolean('is_trial')->default(false)->after('subscription_end_date');
            }
            if (!Schema::hasColumn('businesses', 'trial_end_date')) {
                $table->date('trial_end_date')->nullable()->after('is_trial');
            }
            
            // System management
            if (!Schema::hasColumn('businesses', 'features')) {
                $table->text('features')->nullable()->after('trial_end_date');
            }
            if (!Schema::hasColumn('businesses', 'restrictions')) {
                $table->text('restrictions')->nullable()->after('features');
            }
            if (!Schema::hasColumn('businesses', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('restrictions');
            }
            
            // Audit fields - NULLABLE for foreign keys that use SET NULL
            if (!Schema::hasColumn('businesses', 'created_by_admin_id')) {
                $table->foreignId('created_by_admin_id')->nullable()->after('admin_notes')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('businesses', 'last_active_at')) {
                $table->timestamp('last_active_at')->nullable()->after('created_by_admin_id');
            }
            if (!Schema::hasColumn('businesses', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('last_active_at');
            }
            if (!Schema::hasColumn('businesses', 'suspended_by_admin_id')) {
                $table->foreignId('suspended_by_admin_id')->nullable()->after('suspended_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('businesses', 'suspension_reason')) {
                $table->text('suspension_reason')->nullable()->after('suspended_by_admin_id');
            }
        });

        // Add indexes for businesses table (only if columns exist and index doesn't exist)
        Schema::table('businesses', function (Blueprint $table) {
            if (Schema::hasColumn('businesses', 'subscription_tier') && !$this->indexExists('businesses', 'businesses_subscription_tier_index')) {
                $table->index('subscription_tier');
            }
            if (Schema::hasColumn('businesses', 'subscription_end_date') && !$this->indexExists('businesses', 'businesses_subscription_end_date_index')) {
                $table->index('subscription_end_date');
            }
            if (Schema::hasColumn('businesses', 'is_trial') && !$this->indexExists('businesses', 'businesses_is_trial_index')) {
                $table->index('is_trial');
            }
            if (Schema::hasColumn('businesses', 'last_active_at') && !$this->indexExists('businesses', 'businesses_last_active_at_index')) {
                $table->index('last_active_at');
            }
            if (Schema::hasColumn('businesses', 'suspended_at') && !$this->indexExists('businesses', 'businesses_suspended_at_index')) {
                $table->index('suspended_at');
            }
        });

        // Create business_activity_logs table
        if (!Schema::hasTable('business_activity_logs')) {
            Schema::create('business_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action');
                $table->text('description');
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
                
                $table->index(['business_id', 'created_at']);
                $table->index('action');
            });
        }

        // Create business_limits_history table
        if (!Schema::hasTable('business_limits_history')) {
            Schema::create('business_limits_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                // IMPORTANT: Make this NULLABLE because of nullOnDelete
                $table->foreignId('changed_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->integer('old_limit');
                $table->integer('new_limit');
                $table->text('reason')->nullable();
                $table->timestamps();
                
                $table->index(['business_id', 'created_at']);
            });
        }

        // Update existing admin user to superadmin (optional - customize as needed)
        // Uncomment and modify the email to match your admin
        // DB::table('users')
        //     ->where('email', 'admin@example.com')
        //     ->update(['is_superadmin' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables first
        Schema::dropIfExists('business_limits_history');
        Schema::dropIfExists('business_activity_logs');
        
        // Drop foreign keys and columns from businesses table
        Schema::table('businesses', function (Blueprint $table) {
            // Drop foreign keys first
            if (Schema::hasColumn('businesses', 'created_by_admin_id')) {
                $table->dropForeign(['created_by_admin_id']);
            }
            if (Schema::hasColumn('businesses', 'suspended_by_admin_id')) {
                $table->dropForeign(['suspended_by_admin_id']);
            }
            
            // Drop indexes
            $indexes = [
                'businesses_subscription_tier_index',
                'businesses_subscription_end_date_index',
                'businesses_is_trial_index',
                'businesses_last_active_at_index',
                'businesses_suspended_at_index',
            ];
            
            foreach ($indexes as $index) {
                if ($this->indexExists('businesses', $index)) {
                    $table->dropIndex($index);
                }
            }
            
            // Drop columns
            $columns = [
                'employee_limit',
                'current_employee_count',
                'subscription_tier',
                'subscription_start_date',
                'subscription_end_date',
                'is_trial',
                'trial_end_date',
                'features',
                'restrictions',
                'admin_notes',
                'created_by_admin_id',
                'last_active_at',
                'suspended_at',
                'suspended_by_admin_id',
                'suspension_reason'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('businesses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
        
        // Drop is_superadmin from users table
        Schema::table('users', function (Blueprint $table) {
            if ($this->indexExists('users', 'users_is_superadmin_index')) {
                $table->dropIndex('users_is_superadmin_index');
            }
            if (Schema::hasColumn('users', 'is_superadmin')) {
                $table->dropColumn('is_superadmin');
            }
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $result = DB::select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$databaseName, $table, $index]
        );
        
        return $result[0]->count > 0;
    }
};