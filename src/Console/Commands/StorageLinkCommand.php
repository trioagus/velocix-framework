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
        
        $target = $basePath . '/storage/app/public';
        $link = $basePath . '/public/storage';

        // Check if target exists
        if (!is_dir($target)) {
            mkdir($target, 0755, true);
            $this->info("Created storage directory: {$target}");
        }

        // Remove existing link if it exists
        if (file_exists($link)) {
            if (is_link($link)) {
                unlink($link);
                $this->info('Removed existing symbolic link.');
            } else {
                $this->error('The "public/storage" path already exists and is not a symbolic link.');
                return;
            }
        }

        // Create symbolic link
        if (symlink($target, $link)) {
            $this->info('✓ Symbolic link created successfully!');
            $this->line("  From: {$link}");
            $this->line("  To: {$target}");
        } else {
            $this->error('Failed to create symbolic link.');
            $this->line('Try running with sudo or check permissions.');
        }
    }
}