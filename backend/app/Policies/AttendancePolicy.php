<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendancePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any attendances.
     */
    public function viewAny(User $user): bool
    {
        // Allow admins, HR, and managers to view all attendance
        return in_array($user->role, ['admin', 'hr', 'manager']);
        
        // OR if you're using a different role system:
        // return $user->hasRole(['admin', 'hr', 'manager']);
        
        // OR if you're using permissions:
        // return $user->hasPermission('view_attendance');
    }

    /**
     * Determine if the user can update an attendance record.
     */
    public function update(User $user, Attendance $attendance): bool
    {
        // Allow admins and HR to update any attendance
        if (in_array($user->role, ['admin', 'hr'])) {
            return true;
        }

        // Allow employees to update their own attendance
        return $user->employee && $user->employee->id === $attendance->employee_id;
    }
}