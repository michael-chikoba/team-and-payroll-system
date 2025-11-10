<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function view(User $user, Employee $employee): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isManager()) {
            return $employee->manager_id === $user->id;
        }

        return $employee->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Employee $employee): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isManager()) {
            return $employee->manager_id === $user->id;
        }

        return $employee->user_id === $user->id;
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->isAdmin();
    }

    public function manageSalary(User $user, Employee $employee): bool
    {
        return $user->isAdmin();
    }

    public function viewConfidential(User $user, Employee $employee): bool
    {
        return $user->isAdmin();
    }
}