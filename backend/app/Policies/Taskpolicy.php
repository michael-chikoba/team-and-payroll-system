<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        // Managers can view tasks they created, employees can view tasks assigned to them
        return ($user->role === 'manager' && $task->created_by === $user->id)
            || $task->assigned_to === $user->id;
    }

    public function update(User $user, Task $task): bool
    {
        // Only managers who created the task can update it (title, description, etc.)
        return $user->role === 'manager' && $task->created_by === $user->id;
    }

    public function updateStatus(User $user, Task $task): bool
    {
        // Employees can update status of their assigned tasks
        // Managers can update status of tasks they created
        return $task->assigned_to === $user->id
            || ($user->role === 'manager' && $task->created_by === $user->id);
    }

    public function delete(User $user, Task $task): bool
    {
        // Only managers who created the task can delete it
        return $user->role === 'manager' && $task->created_by === $user->id;
    }
}