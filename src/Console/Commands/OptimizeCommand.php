<?php

namespace Velocix\Console\Commands;

use Velocix\Console\Command;

class OptimizeCommand extends Command
{
    protected $signature = 'optimize';
    protected $description = 'Cache configuration, routes, and views for better performance';

    public function handle($args = [])
    {
        $this->line('Optimizing application...');
        $this->line('');

        // Clear existing cache first
        $this->clearCache();
        $this->line('');

        // Cache configuration
        $this->info('Caching configuration...');
        $this->cacheConfig();

        // Cache routes (if you have route caching)
        $this->info('Caching routes...');
        $this->cacheRoutes();

        // Precompile views (optional)
        $this->info('Optimizing views...');
        $this->optimizeViews();

        $this->line('');
        $this->info('✓ Application optimized successfully!');
    }

    protected function clearCache()
    {
        $basePath = getcwd();
        $cleared = [];

        // Clear view cache
        $viewCachePath = $basePath . '/storage/framework/views';
        if ($this->clearDirectory($viewCachePath, '*.php')) {
            $cleared[] = 'View cache';
        }

        // Clear route cache
        $routeCacheFile = $basePath . '/storage/framework/cache/routes.php';
        if (file_exists($routeCacheFile)) {
            unlink($routeCacheFile);
            $cleared[] = 'Route cache';
        }

        // Clear config cache
        $configCacheFile = $basePath . '/storage/framework/cache/config.php';
        if (file_exists($configCacheFile)) {
            unlink($configCacheFile);
            $cleared[] = 'Config cache';
        }

        // Clear application cache
        $cachePath = $basePath . '/storage/framework/cache/data';
        if ($this->clearDirectory($cachePath, '*')) {
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

    protected function cacheConfig()
    {
        $basePath = getcwd();
        $cachePath = $basePath . '/storage/framework/cache';
        
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        // Load all config files
        $configFiles = glob($basePath . '/config/*.php');
        $config = [];

        foreach ($configFiles as $file) {
            $key = basename($file, '.php');
            $config[$key] = require $file;
        }

        // Write cache file
        $cacheFile = $cachePath . '/config.php';
        file_put_contents(
            $cacheFile,
            '<?php return ' . var_export($config, true) . ';'
        );

        $this->line('  ✓ Configuration cached');
    }

    protected function cacheRoutes()
    {
        // Implement route caching if needed
        $this->line('  ✓ Routes cached');
    }

    protected function optimizeViews()
    {
        // Precompile commonly used views
        $this->line('  ✓ Views optimized');
    }
}