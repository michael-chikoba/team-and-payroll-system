<?php
// Create: app/Console/Commands/SetupChatSystem.php
// Run: php artisan chat:setup

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\Employee;

class SetupChatSystem extends Command
{
    protected $signature = 'chat:setup {--force : Force setup even if already configured}';
    protected $description = 'Setup and verify chat system configuration';

    public function handle()
    {
        $this->info('=== Chat System Setup ===');
        $this->newLine();

        // Step 1: Check tables
        $this->info('Step 1: Checking database tables...');
        $this->checkTables();
        $this->newLine();

        // Step 2: Run migrations
        $this->info('Step 2: Running migrations...');
        if ($this->confirm('Do you want to run migrations?', true)) {
            Artisan::call('migrate');
            $this->info('✓ Migrations completed');
        }
        $this->newLine();

        // Step 3: Verify models
        $this->info('Step 3: Verifying models...');
        $this->verifyModels();
        $this->newLine();

        // Step 4: Check user data
        $this->info('Step 4: Checking user data integrity...');
        $this->checkUserData();
        $this->newLine();

        $this->info('=== Setup Complete ===');
        $this->info('You can now test the chat system!');
    }

    private function checkTables()
    {
        $requiredTables = [
            'chat_groups' => ['id', 'business_id', 'name', 'type', 'created_by', 'is_active'],
            'chat_group_members' => ['id', 'chat_group_id', 'user_id', 'role', 'last_read_at'],
            'chat_messages' => ['id', 'chat_group_id', 'user_id', 'message', 'type'],
        ];

        foreach ($requiredTables as $table => $columns) {
            if (Schema::hasTable($table)) {
                $this->line("  ✓ Table '{$table}' exists");
                
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $this->line("    ✓ Column '{$column}'");
                    } else {
                        $this->error("    ✗ Column '{$column}' MISSING!");
                    }
                }
            } else {
                $this->error("  ✗ Table '{$table}' MISSING!");
                $this->warn("    Run: php artisan make:migration create_{$table}_table");
            }
        }
    }

    private function verifyModels()
    {
        try {
            $reflection = new \ReflectionClass(\App\Models\ChatGroup::class);
            $methods = $reflection->getMethods();
            
            $requiredMethods = ['forUser', 'active', 'isMember', 'isAdmin', 'addMember'];
            $foundMethods = array_map(fn($m) => $m->name, $methods);
            
            foreach ($requiredMethods as $method) {
                if (in_array($method, $foundMethods)) {
                    $this->line("  ✓ ChatGroup::{$method}() exists");
                } else {
                    $this->error("  ✗ ChatGroup::{$method}() MISSING!");
                }
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Error checking models: " . $e->getMessage());
        }
    }

    private function checkUserData()
    {
        $totalUsers = User::count();
        $usersWithEmployees = User::has('employee')->count();
        $employeesWithBusiness = Employee::whereNotNull('business_id')->count();

        $this->line("  Total users: {$totalUsers}");
        $this->line("  Users with employee records: {$usersWithEmployees}");
        $this->line("  Employees with business_id: {$employeesWithBusiness}");

        if ($totalUsers > $usersWithEmployees) {
            $this->warn("  ⚠ " . ($totalUsers - $usersWithEmployees) . " users missing employee records");
            $this->warn("    These users won't be able to use chat!");
        }

        if ($usersWithEmployees > $employeesWithBusiness) {
            $this->warn("  ⚠ " . ($usersWithEmployees - $employeesWithBusiness) . " employees missing business_id");
            $this->warn("    These employees won't be able to use chat!");
        }

        if ($totalUsers === $usersWithEmployees && $usersWithEmployees === $employeesWithBusiness) {
            $this->info("  ✓ All user data is properly configured!");
        }
    }
}