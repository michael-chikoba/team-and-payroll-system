<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        $defaultSettings = [
            [
                'key' => 'company_name',
                'value' => 'Payroll System',
                'description' => 'Company name for the system'
            ],
            [
                'key' => 'currency',
                'value' => 'ZMW',
                'description' => 'Default currency for payroll'
            ],
            [
                'key' => 'annual_leave_days',
                'value' => '21',
                'description' => 'Default annual leave days per year'
            ],
            [
                'key' => 'sick_leave_days',
                'value' => '7',
                'description' => 'Default sick leave days per year'
            ],
            [
                'key' => 'default_password',
                'value' => 'Password123!',
                'description' => 'Default password for new employees'
            ],
            [
                'key' => 'max_login_attempts',
                'value' => '5',
                'description' => 'Maximum login attempts before lockout'
            ],
            [
                'key' => 'session_timeout',
                'value' => '60',
                'description' => 'Session timeout in minutes'
            ],
            [
                'key' => 'departments',
                'value' => json_encode([
                    ['name' => 'IT'],
                    ['name' => 'HR'],
                    ['name' => 'Finance'],
                    ['name' => 'Sales'],
                    ['name' => 'Marketing'],
                    ['name' => 'Operations']
                ]),
                'description' => 'Available departments in the company'
            ]
        ];

        foreach ($defaultSettings as $setting) {
            \App\Models\SystemSetting::create($setting);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};