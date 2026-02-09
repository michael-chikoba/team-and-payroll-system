<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:generate-vapid-keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate VAPID keys for web push notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating VAPID keys...');
        
        try {
            $keys = VAPID::createVapidKeys();
            
            $this->newLine();
            $this->info('✅ VAPID keys generated successfully!');
            $this->newLine();
            
            $this->line('Add the following to your .env file:');
            $this->newLine();
            
            $this->line('VAPID_SUBJECT=mailto:' . config('mail.from.address', 'admin@example.com'));
            $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
            $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
            
            $this->newLine();
            $this->line('Also add the public key to your frontend .env file:');
            $this->newLine();
            $this->line('VITE_VAPID_PUBLIC_KEY=' . $keys['publicKey']);
            
            $this->newLine();
            $this->warn('⚠️  Keep the private key secret! Never expose it to the client.');
            
            // Optionally write to .env file
            if ($this->confirm('Do you want to automatically add these to your .env file?', false)) {
                $this->updateEnvFile($keys);
            }
            
        } catch (\Exception $e) {
            $this->error('Failed to generate VAPID keys: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Update the .env file with VAPID keys
     */
    private function updateEnvFile(array $keys)
    {
        $envPath = base_path('.env');
        
        if (!file_exists($envPath)) {
            $this->error('.env file not found!');
            return;
        }
        
        $envContent = file_get_contents($envPath);
        
        // Check if keys already exist
        if (strpos($envContent, 'VAPID_PUBLIC_KEY=') !== false) {
            if (!$this->confirm('VAPID keys already exist in .env. Overwrite?', false)) {
                return;
            }
            
            // Remove existing keys
            $envContent = preg_replace('/VAPID_SUBJECT=.*/m', '', $envContent);
            $envContent = preg_replace('/VAPID_PUBLIC_KEY=.*/m', '', $envContent);
            $envContent = preg_replace('/VAPID_PRIVATE_KEY=.*/m', '', $envContent);
        }
        
        // Add new keys
        $newKeys = "\n# VAPID Keys for Push Notifications\n";
        $newKeys .= "VAPID_SUBJECT=mailto:" . config('mail.from.address', 'admin@example.com') . "\n";
        $newKeys .= "VAPID_PUBLIC_KEY=" . $keys['publicKey'] . "\n";
        $newKeys .= "VAPID_PRIVATE_KEY=" . $keys['privateKey'] . "\n";
        
        file_put_contents($envPath, $envContent . $newKeys);
        
        $this->info('✅ VAPID keys added to .env file');
    }
}