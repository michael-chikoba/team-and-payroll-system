<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\BusinessAdmin;
use App\Models\Business;

class ArchTechAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the Arch Tech business
        $business = Business::find(2); // Arch Tech business ID

        if (!$business) {
            $this->command->error('Arch Tech business not found!');
            return;
        }

        // Create admin user
        $adminUser = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Arch Tech',
            'email' => 'admin@archtech.com',
            'password' => Hash::make('password'), // Change this to a secure password
            'role' => 'admin',
            'email_verified_at' => now(),
            'current_business_id' => $business->id,
        ]);

        // Assign admin role using Spatie permissions (if you're using it)
        if (method_exists($adminUser, 'assignRole')) {
            $adminUser->assignRole('admin');
        }

        // Create business admin record
        BusinessAdmin::create([
            'business_id' => $business->id,
            'user_id' => $adminUser->id,
            'role' => 'admin',
            'is_primary' => 0, // Not primary since user 51 is already primary owner
        ]);

        $this->command->info('✅ Admin user created successfully!');
        $this->command->info('📧 Email: admin@archtech.com');
        $this->command->info('🔑 Password: password');
        $this->command->info('⚠️  Please change the password after first login!');
    }
}