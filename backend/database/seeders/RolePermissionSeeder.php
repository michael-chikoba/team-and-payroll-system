<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Employee permissions
            'view_attendance',
            'clock_in',
            'clock_out',
            'apply_leave',
            'view_payslips',
            'view_profile',

            // Manager permissions
            'view_team_attendance',
            'view_team_leaves',
            'approve_leaves',
            'view_team_reports',
            'manage_team',

            // Admin permissions
            'manage_employees',
            'manage_payroll',
            'process_payroll',
            'generate_reports',
            'manage_system',
            'view_audit_logs',
            'configure_tax',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);
        $employeePermissions = [
            'view_attendance',
            'clock_in',
            'clock_out',
            'apply_leave',
            'view_payslips',
            'view_profile',
        ];
        $employeeRole->syncPermissions($employeePermissions);

        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $managerPermissions = array_merge($employeePermissions, [
            'view_team_attendance',
            'view_team_leaves',
            'approve_leaves',
            'view_team_reports',
            'manage_team',
        ]);
        $managerRole->syncPermissions($managerPermissions);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminPermissions = array_merge($managerPermissions, [
            'manage_employees',
            'manage_payroll',
            'process_payroll',
            'generate_reports',
            'manage_system',
            'view_audit_logs',
            'configure_tax',
        ]);
        $adminRole->syncPermissions($adminPermissions);
    }
}