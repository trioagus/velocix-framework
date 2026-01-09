<?php

namespace Velocix\Console\Commands;

use Velocix\Console\Command;

class ServeCommand extends Command
{
    protected $signature = 'serve';
    protected $description = 'Start the development server';

    public function handle($args = [])
    {
        $host = $args[0] ?? 'localhost';
        $port = $args[1] ?? '8000';
        
        $basePath = getcwd();
        $routerPath = $basePath . '/server.php';
        
        // Create server router if not exists
        if (!file_exists($routerPath)) {
            $this->createServerRouter($routerPath);
        }

        $this->info("⚡ Velocix development server started");
        $this->line("   Server running at: http://{$host}:{$port}");
        $this->line("   Press Ctrl+C to stop");
        $this->line('');

        passthru("php -S {$host}:{$port} -t public {$routerPath}");
    }
    
    protected function createServerRouter($path)
    {
        $content = <<<'PHP'
<?php

/**
 * Velocix Development Server Router
 * This file handles serving static files including symlinked storage
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$publicPath = __DIR__ . '/public';

// Serve static files from storage symlink
if (preg_match('/^\/storage\/(.*)$/', $uri, $matches)) {
    $filePath = $publicPath . '/storage/' . $matches[1];
    
    if (is_file($filePath)) {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'css' => 'text/css',
            'js' => 'text/javascript',
            'json' => 'application/json',
        ];
        
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
        
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: public, max-age=31536000');
        readfile($filePath);
        return true;
    }
}

// Serve other static files
if (is_file($publicPath . $uri)) {
    return false;
}

// Route to index.php
$_SERVER['SCRIPT_FILENAME'] = $publicPath . '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $publicPath . '/index.php';
PHP;
        
        file_put_contents($path, $content);
        $this->info("✓ Created server router: server.php");
    }
}