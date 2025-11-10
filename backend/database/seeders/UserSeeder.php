<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@payroll.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create Manager User
        $manager = User::create([
            'name' => 'michael Manager',
            'email' => 'manager@payroll.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
        ]);

        // Create Employee Users
        User::create([
            'name' => 'Alice Johnson',
            'email' => 'alice@payroll.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
        ]);

        User::create([
            'name' => 'Bob Smith',
            'email' => 'bob@payroll.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
        ]);
    }
}