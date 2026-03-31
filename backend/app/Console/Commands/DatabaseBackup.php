<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DatabaseBackup extends Command
{
    protected $signature = 'db:backup 
                            {--database= : The database connection to backup}
                            {--path= : Custom path to save the backup}
                            {--compress : Compress the backup with gzip}';
    
    protected $description = 'Create a MySQL database dump';

    public function handle()
    {
        $connection = $this->option('database') ?? config('database.default');
        $config = config("database.connections.$connection");
        
        if (!$config || $config['driver'] !== 'mysql') {
            $this->error("Invalid or non-MySQL connection: $connection");
            return 1;
        }

        // Generate filename
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$config['database']}_{$timestamp}.sql";
        
        // Determine path
        $path = $this->option('path') 
            ? rtrim($this->option('path'), '/') . '/' . $filename
            : storage_path("app/backups/$filename");

        // Create directory if it doesn't exist
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Build mysqldump command
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s --port=%s %s %s > %s',
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['host']),
            escapeshellarg($config['port'] ?? 3306),
            $this->getMysqldumpOptions(),
            escapeshellarg($config['database']),
            escapeshellarg($path)
        );

        // Add compression if requested
        if ($this->option('compress')) {
            $command .= ' && gzip ' . escapeshellarg($path);
            $path .= '.gz';
        }

        $this->info("Creating backup of database: {$config['database']}");
        
        $output = null;
        $returnVar = null;
        exec($command . ' 2>&1', $output, $returnVar);

        if ($returnVar === 0) {
            $this->info("Backup created successfully: $path");
            $this->info("File size: " . $this->formatBytes(filesize($path)));
        } else {
            $this->error("Backup failed: " . implode("\n", $output));
        }

        return $returnVar;
    }

    protected function getMysqldumpOptions()
    {
        // Add any additional mysqldump options here
        $options = [
            '--single-transaction', // For InnoDB consistency
            '--routines',           // Include stored procedures
            '--events',             // Include events
            '--triggers',            // Include triggers
        ];

        return implode(' ', $options);
    }

    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
}