<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Get Zambia country ID
       // $zambia = Country::where('code', 'ZM')->first();
        
        // if (!$zambia) {
        //     $this->command->error('Zambia country not found. Please run CountrySeeder first.');
        //     return;
        // }

        // Create Admin User - Michael Chikoba
        User::create([
            'first_name' => 'Michael',
            'last_name' => 'Chikoba',
            'email' => 'michaelchikoba0@gmail.com',
            'password' => Hash::make('michael@test'),
            'role' => 'admin',
        ]);

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: michaelchikoba0@gmail.com');
        $this->command->info('Password: michael@test');
    }
}