<?php

namespace Velocix\Support;

class Vite
{
    protected static $manifest = null;
    protected static $devServerUrl = 'http://localhost:5173';
    
    public static function assets()
    {
        if (static::isRunningHot()) {
            return static::makeDevTags();
        }
        
        return static::makeProdTags();
    }
    
    protected static function isRunningHot()
    {
        $hotFile = static::getBasePath() . '/public/hot';
        return file_exists($hotFile);
    }
    
    protected static function makeDevTags()
    {
        return sprintf(
            '<script type="module" src="%s/@vite/client"></script>' . PHP_EOL .
            '<script type="module" src="%s/resources/js/app.js"></script>',
            static::$devServerUrl,
            static::$devServerUrl
        );
    }
    
    protected static function makeProdTags()
    {
        $manifest = static::getManifest();
        
        if (!$manifest) {
            return '';
        }
        
        $output = '';
        
        if (isset($manifest['resources/js/app.js'])) {
            $entry = $manifest['resources/js/app.js'];
            
            // Load CSS files
            if (isset($entry['css'])) {
                foreach ($entry['css'] as $cssFile) {
                    $output .= sprintf(
                        '<link rel="stylesheet" href="/build/%s">' . PHP_EOL, 
                        $cssFile
                    );
                }
            }
            
            // Load JS file
            if (isset($entry['file'])) {
                $output .= sprintf(
                    '<script type="module" src="/build/%s"></script>',
                    $entry['file']
                );
            }
        }
        
        return $output;
    }
    
    protected static function getManifest()
    {
        if (static::$manifest !== null) {
            return static::$manifest;
        }
        
        $manifestPath = static::getBasePath() . '/public/build/manifest.json';
        
        if (!file_exists($manifestPath)) {
            return null;
        }
        
        static::$manifest = json_decode(
            file_get_contents($manifestPath), 
            true
        );
        
        return static::$manifest;
    }
    
    protected static function getBasePath()
    {
        static $basePath = null;
        
        if ($basePath !== null) {
            return $basePath;
        }
        
        // Try vendor directory (3 levels up)
        $basePath = dirname(__DIR__, 3);
        
        // If not in vendor, use getcwd
        if (!file_exists($basePath . '/public')) {
            $basePath = getcwd();
            
            // If in public directory, go up
            if (basename($basePath) === 'public') {
                $basePath = dirname($basePath);
            }
        }
        
        return $basePath;
    }
    
    public static function asset($path)
    {
        if (static::isRunningHot()) {
            return static::$devServerUrl . '/' . ltrim($path, '/');
        }
        
        $manifest = static::getManifest();
        
        if ($manifest && isset($manifest[$path]['file'])) {
            return '/build/' . $manifest[$path]['file'];
        }
        
        return '/' . ltrim($path, '/');
    }
}

// Helper function - CRITICAL: Tanpa parameter!
if (!function_exists('vite')) {
    function vite()
    {
        return \Velocix\Support\Vite::assets();
    }
}