<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait ScopedByDepartment
{
    /**
     * Boot the scope.
     */
    protected static function bootScopedByDepartment()
    {
        // Only apply if a user is logged in and NOT running in console
        if (Auth::check() && !app()->runningInConsole()) {
            static::addGlobalScope('department', function (Builder $builder) {
                $user = Auth::user();

                // Admins see everything
                if ($user->hasRole(['admin', 'super-admin'])) {
                    return;
                }

                // Users only see data from their department
                // Assumes the model has a 'department' column
                if ($user->department) {
                    $builder->where('department', $user->department);
                }
            });
        }
    }
}