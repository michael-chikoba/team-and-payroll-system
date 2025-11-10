<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use App\Models\Attendance;
use App\Policies\AttendancePolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Employee::class => \App\Policies\EmployeePolicy::class,
        \App\Models\Leave::class => \App\Policies\LeavePolicy::class,
        \App\Models\Payroll::class => \App\Policies\PayrollPolicy::class,
        \App\Models\Payslip::class => \App\Policies\PayslipPolicy::class,
        Attendance::class => AttendancePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Sanctum::usePersonalAccessTokenModel(\Laravel\Sanctum\PersonalAccessToken::class);

        // Define role-based gates
        Gate::define('admin-access', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('manager-access', function ($user) {
            return $user->isManager() || $user->isAdmin();
        });

        Gate::define('employee-access', function ($user) {
            return $user->isEmployee() || $user->isManager() || $user->isAdmin();
        });

        // Define specific permission gates
        Gate::define('manage-payroll', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('approve-leaves', function ($user) {
            return $user->isManager() || $user->isAdmin();
        });

        Gate::define('view-reports', function ($user) {
            return $user->isManager() || $user->isAdmin();
        });
    }
}