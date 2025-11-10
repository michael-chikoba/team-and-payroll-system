<?php

namespace App\Policies;

use App\Models\Payroll;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayrollPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function view(User $user, Payroll $payroll): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isManager()) {
            // Managers can only view payrolls that include their team members
            return $payroll->payslips()
                ->whereHas('employee', function ($query) use ($user) {
                    $query->where('manager_id', $user->id);
                })
                ->exists();
        }

        // Employees can only view their own payslips within payroll
        return $payroll->payslips()
            ->where('employee_id', $user->employee?->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Payroll $payroll): bool
    {
        return $user->isAdmin() && $payroll->status === 'draft';
    }

    public function delete(User $user, Payroll $payroll): bool
    {
        return $user->isAdmin() && $payroll->status === 'draft';
    }

    public function process(User $user, Payroll $payroll): bool
    {
        return $user->isAdmin() && in_array($payroll->status, ['draft', 'failed']);
    }

    public function generatePayslips(User $user, Payroll $payroll): bool
    {
        return $user->isAdmin() && $payroll->status === 'completed';
    }
}