<?php

use Velocix\Support\Logger;
use Velocix\Database\Connection;
use Velocix\Database\QueryBuilder;

if (!function_exists('db')) {
    /**
     * Get database connection or query builder
     *
     * @param string|null $table
     * @return Connection|QueryBuilder
     */
    function db($table = null)
    {
        static $connection;

        if (!$connection) {
            // Get database config
            $default = config('database.default', 'mysql');
            $config = config("database.connections.{$default}");

            if (!$config) {
                throw new \Exception("Database connection [{$default}] not configured.");
            }

            // Use Connection::make() - singleton pattern
            $connection = Connection::make($config);
        }

        if ($table) {
            return $connection->table($table);
        }

        return $connection;
    }
}

if (!function_exists('now')) {
    /**
     * Get current datetime
     */
    function now()
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('today')) {
    /**
     * Get current date
     */
    function today()
    {
        return date('Y-m-d');
    }
}


if (!function_exists('app')) {
    function app($abstract = null)
    {
        if (is_null($abstract)) {
            return \Velocix\Foundation\Application::getInstance();
        }
        
        return \Velocix\Foundation\Application::getInstance()->make($abstract);
    }
}

if (!function_exists('view')) {
    function view($view, $data = [])
    {
        static $factory = null;
        
        if (is_null($factory)) {
            $app = app();
            $basePath = $app->basePath();
            $viewPath = $basePath . '/resources/views';
            $cachePath = $basePath . '/storage/framework/views';
            
            if (!is_dir($cachePath)) {
                mkdir($cachePath, 0755, true);
            }
            
            $factory = new \Velocix\View\Factory($viewPath, $cachePath);
        }
        
        return $factory->make($view, $data);
    }
}

if (!function_exists('response')) {
    function response($content = '', $status = 200, $headers = [])
    {
        return new \Velocix\Http\Response($content, $status, $headers);
    }
}

if (!function_exists('json')) {
    function json($data, $status = 200, $headers = [])
    {
        return \Velocix\Http\Response::json($data, $status, $headers);
    }
}

if (!function_exists('redirect')) {
    function redirect($url)
    {
        header("Location: {$url}");
        exit;
    }
}

if (!function_exists('back')) {
    function back()
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return redirect($referer);
    }
}

if (!function_exists('request')) {
    function request()
    {
        return app('request');
    }
}

if (!function_exists('session')) {
    function session($key = null, $default = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (is_null($key)) {
            return $_SESSION;
        }
        
        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('old')) {
    function old($key, $default = null)
    {
        return session("_old_input.{$key}", $default);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field()
    {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('method_field')) {
    function method_field($method)
    {
        return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
    }
}

if (!function_exists('asset')) {
    function asset($path)
    {
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url($path = '')
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
            ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        return $protocol . '://' . $host . '/' . ltrim($path, '/');
    }
}

if (!function_exists('route')) {
    function route($name, $parameters = [])
    {
        // Simplified route helper
        $path = $name;
        
        foreach ($parameters as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
        }
        
        return url($path);
    }
}

if (!function_exists('config')) {
    function config($key, $default = null)
    {
        static $config = null;
        
        if (is_null($config)) {
            $config = [];
            $configPath = app()->basePath('config');
            
            if (is_dir($configPath)) {
                foreach (glob($configPath . '/*.php') as $file) {
                    $name = basename($file, '.php');
                    $config[$name] = require $file;
                }
            }
        }
        
        $keys = explode('.', $key);
        $value = $config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
}

if (!function_exists('env')) {
    function env($key, $default = null)
    {
        static $loaded = false;
        
        if (!$loaded) {
            $envFile = app()->basePath('.env');
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) {
                        continue;
                    }
                    
                    list($name, $value) = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    
                    if (!array_key_exists($name, $_ENV)) {
                        $_ENV[$name] = $value;
                        putenv("{$name}={$value}");
                    }
                }
            }
            $loaded = true;
        }
        
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

if (!function_exists('dd')) {
    function dd(...$vars)
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
        die;
    }
}

if (!function_exists('dump')) {
    function dump(...$vars)
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
    }
}

if (!function_exists('vite')) {
    function vite()
    {
        return \Velocix\Support\Vite::assets();
    }
}

if (!function_exists('is_spa_request')) {
    function is_spa_request()
    {
        return isset($_SERVER['HTTP_X_VELOCIX_SPA']) && 
               $_SERVER['HTTP_X_VELOCIX_SPA'] === 'true';
    }
}

if (!function_exists('logger')) {
    function logger($message = null, array $context = [])
    {
        static $logger;

        if (!$logger) {
            $logger = new Logger();
        }

        if (is_null($message)) {
            return $logger;
        }

        return $logger->info($message, $context);
    }
}
