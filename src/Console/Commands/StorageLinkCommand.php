<?php

namespace Velocix\Console\Commands;

use Velocix\Console\Command;

class StorageLinkCommand extends Command
{
    protected $signature = 'storage:link';
    protected $description = 'Create symbolic link from public/storage to storage/app/public';

    public function handle($args = [])
    {
        $basePath = getcwd();
        
        // Load filesystem config
        $configPath = $basePath . '/config/filesystem.php';
        
        if (!file_exists($configPath)) {
            $this->error('Config file not found: config/filesystem.php');
            return;
        }
        
        $config = require $configPath;
        $links = $config['links'] ?? [];
        
        if (empty($links)) {
            $this->error('No links configured in config/filesystem.php');
            return;
        }
        
        foreach ($links as $link => $target) {
            $this->createLink($link, $target);
        }
    }
    
    protected function createLink($link, $target)
    {
        // Ensure target directory exists
        if (!is_dir($target)) {
            mkdir($target, 0755, true);
            $this->info("Created target directory: {$target}");
        }
        
        // Remove existing link if exists
        if (file_exists($link)) {
            if (is_link($link)) {
                unlink($link);
                $this->info("Removed existing link: {$link}");
            } else {
                $this->error("Path already exists and is not a symlink: {$link}");
                $this->line('Please remove or rename it first.');
                return;
            }
        }
        
        // Create parent directory if needed
        $linkDir = dirname($link);
        if (!is_dir($linkDir)) {
            mkdir($linkDir, 0755, true);
        }
        
        // Create symbolic link
        if (symlink($target, $link)) {
            $this->info("✓ Linked: {$link}");
            $this->line("  → {$target}");
        } else {
            $this->error("Failed to create symlink: {$link}");
        }
    }
}