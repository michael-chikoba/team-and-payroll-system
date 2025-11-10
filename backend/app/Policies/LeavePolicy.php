<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeavePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view leaves
    }

    public function view(User $user, Leave $leave): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isManager()) {
            return $leave->manager_id === $user->id || $leave->employee->manager_id === $user->id;
        }

        return $leave->employee_id === $user->employee?->id;
    }

    public function create(User $user): bool
    {
        return $user->isEmployee();
    }

    public function update(User $user, Leave $leave): bool
    {
        if ($leave->status !== 'pending') {
            return false;
        }

        return $leave->employee_id === $user->employee?->id;
    }

    public function delete(User $user, Leave $leave): bool
    {
        if ($leave->status !== 'pending') {
            return false;
        }

        return $leave->employee_id === $user->employee?->id;
    }

    public function approve(User $user, Leave $leave): bool
    {
        if ($leave->status !== 'pending') {
            return false;
        }

        return $user->isManager() && $leave->manager_id === $user->id;
    }

    public function reject(User $user, Leave $leave): bool
    {
        return $this->approve($user, $leave);
    }
}