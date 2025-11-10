<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('employee.{employeeId}', function ($user, $employeeId) {
    return $user->employee && (int) $user->employee->id === (int) $employeeId;
});

Broadcast::channel('manager.{managerId}', function ($user, $managerId) {
    return $user->manager && (int) $user->manager->id === (int) $managerId;
});

Broadcast::channel('admin', function ($user) {
    return $user->isAdmin();
});