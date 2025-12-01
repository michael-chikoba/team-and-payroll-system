<?php
// app/Policies/BusinessPolicy.php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BusinessPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Business $business): bool
    {
        return $user->businesses()->where('businesses.id', $business->id)->exists();
    }

    public function update(User $user, Business $business): bool
    {
        return $user->businessAdmins()
            ->where('business_id', $business->id)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, Business $business): bool
    {
        return $user->businessAdmins()
            ->where('business_id', $business->id)
            ->where('role', 'owner')
            ->exists();
    }
}