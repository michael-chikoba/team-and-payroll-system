<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Admin-level restriction permissions (these are "cannot do" flags)
        Schema::create('admin_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            // Restriction flags — true = admin is BLOCKED from this action
            $table->boolean('cannot_add_employee')->default(false);
            $table->boolean('cannot_view_payroll')->default(false);
            $table->boolean('cannot_view_payslip')->default(false);
            $table->boolean('cannot_manage_admins')->default(false);
            // Account status
            $table->boolean('is_suspended')->default(false);
            $table->text('suspension_reason')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->foreignId('suspended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'business_id']);
        });

        // 2. Seed the four new admin-restriction permissions into the Spatie permissions table
        $now = now();
        $perms = [
            ['name' => 'cannot_add_employee',   'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'cannot_view_payroll',   'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'cannot_view_payslip',   'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'cannot_manage_admins',  'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($perms as $perm) {
            DB::table('permissions')->insertOrIgnore($perm);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_permissions');

        DB::table('permissions')
            ->whereIn('name', [
                'cannot_add_employee',
                'cannot_view_payroll',
                'cannot_view_payslip',
                'cannot_manage_admins',
            ])
            ->delete();
    }
};