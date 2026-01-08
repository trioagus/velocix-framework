<?php

namespace Velocix\Console\Commands;

use Velocix\Console\Command;

class CacheClearCommand extends Command
{
    protected $signature = 'cache:clear';
    protected $description = 'Clear application cache';

    public function handle($args = [])
    {
        $basePath = $this->getBasePath();
        $cleared = [];

        if ($this->clearViewCache($basePath)) {
            $cleared[] = 'View cache';
        }

        if ($this->clearRouteCache($basePath)) {
            $cleared[] = 'Route cache';
        }

        // Clear config cache
        if ($this->clearConfigCache($basePath)) {
            $cleared[] = 'Config cache';
        }

        // Clear application cache
        if ($this->clearAppCache($basePath)) {
            $cleared[] = 'Application cache';
        }

        if (empty($cleared)) {
            $this->info('No cache to clear.');
        } else {
            $this->info('✓ Cache cleared successfully!');
            foreach ($cleared as $item) {
                $this->line("  - {$item}");
            }
        }
    }

    protected function getBasePath()
    {
        // Get base path dari current working directory
        return getcwd();
    }

    protected function clearViewCache($basePath)
    {
        $viewCachePath = $basePath . '/storage/framework/views';
        return $this->clearDirectory($viewCachePath, '*.php');
    }

    protected function clearRouteCache($basePath)
    {
        $routeCacheFile = $basePath . '/storage/framework/cache/routes.php';
        if (file_exists($routeCacheFile)) {
            unlink($routeCacheFile);
            return true;
        }
        return false;
    }

    protected function clearConfigCache($basePath)
    {
        $configCacheFile = $basePath . '/storage/framework/cache/config.php';
        if (file_exists($configCacheFile)) {
            unlink($configCacheFile);
            return true;
        }
        return false;
    }

    protected function clearAppCache($basePath)
    {
        $cachePath = $basePath . '/storage/framework/cache/data';
        return $this->clearDirectory($cachePath, '*');
    }

    protected function clearDirectory($path, $pattern = '*')
    {
        if (!is_dir($path)) {
            return false;
        }

        $files = glob($path . '/' . $pattern);
        $cleared = false;

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $cleared = true;
            }
        }

        return $cleared;
    }
}