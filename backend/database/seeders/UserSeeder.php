<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Business;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin User
        $superAdmin = User::create([
            'first_name' => 'Michael',
            'last_name' => 'Chikoba',
            'email' => 'michaelchikoba0@gmail.com',
            'password' => Hash::make('michael@test'),
            'role' => 'super_admin', // New role
        ]);
        
        // Grant access to all existing businesses
        $businesses = Business::all();
        foreach ($businesses as $business) {
            $superAdmin->accessibleBusinesses()->attach($business->id, [
                'role' => 'owner',
                'is_primary' => false
            ]);
        }
        
        // Set primary business if any exists
        if ($businesses->count() > 0) {
            $superAdmin->accessibleBusinesses()->updateExistingPivot(
                $businesses->first()->id,
                ['is_primary' => true]
            );
            $superAdmin->current_business_id = $businesses->first()->id;
            $superAdmin->save();
        }
        
        $this->command->info('Super Admin created successfully!');
        $this->command->info('Email: michaelchikoba0@gmail.com');
        $this->command->info('Password: michael@test');
    }
}