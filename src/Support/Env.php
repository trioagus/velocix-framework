<?php

namespace Velocix\Support;

class Env
{
    protected static $variables = [];
    protected static $loaded = false;

    public static function load($path = null)
    {
        if (static::$loaded) {
            return;
        }

        // Handle null path
        if ($path === null) {
            $path = getcwd() . '/.env';
        }

        $envFile = $path;
        
        // If path is a directory, append .env
        if (is_dir($envFile)) {
            $envFile = rtrim($envFile, '/') . '/.env';
        }

        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse line
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                
                $name = trim($name);
                $value = trim($value);

                // Remove quotes
                $value = static::parseValue($value);

                // Set in environment
                static::$variables[$name] = $value;
                
                // Also set in $_ENV and putenv for compatibility
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
                putenv("{$name}={$value}");
            }
        }

        static::$loaded = true;
    }

    protected static function parseValue($value)
    {
        // Remove quotes
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $value = $matches[1];
        } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
            $value = $matches[1];
        }

        // Handle variable substitution ${VAR} or $VAR
        // Only substitute if variable already exists in parsed variables
        $value = preg_replace_callback('/\$\{([a-zA-Z0-9_]+)\}/', function($matches) {
            $key = $matches[1];
            // Only get from already parsed variables, don't trigger load
            return static::$variables[$key] ?? $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: '';
        }, $value);

        $value = preg_replace_callback('/\$([a-zA-Z0-9_]+)/', function($matches) {
            $key = $matches[1];
            // Only get from already parsed variables, don't trigger load
            return static::$variables[$key] ?? $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: '';
        }, $value);

        // Convert boolean strings
        $lower = strtolower($value);
        if ($lower === 'true' || $lower === '(true)') {
            return true;
        } elseif ($lower === 'false' || $lower === '(false)') {
            return false;
        } elseif ($lower === 'null' || $lower === '(null)') {
            return null;
        } elseif ($lower === 'empty' || $lower === '(empty)') {
            return '';
        }

        return $value;
    }

    public static function get($key, $default = null)
    {
        if (!static::$loaded) {
            static::load();
        }

        // Check in our parsed variables first
        if (isset(static::$variables[$key])) {
            return static::$variables[$key];
        }

        // Fallback to $_ENV and getenv()
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        return $default;
    }

    public static function set($key, $value)
    {
        static::$variables[$key] = $value;
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }

    public static function has($key)
    {
        return static::get($key) !== null;
    }

    public static function all()
    {
        if (!static::$loaded) {
            static::load();
        }

        return static::$variables;
    }
}

// Helper function
if (!function_exists('env')) {
    function env($key, $default = null)
    {
        return \Velocix\Support\Env::get($key, $default);
    }
}