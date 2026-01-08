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

        $this->info("⚡ Velocix development server started");
        $this->line("   Server running at: http://{$host}:{$port}");
        $this->line("   Press Ctrl+C to stop");
        $this->line('');

        passthru("php -S {$host}:{$port} -t public");
    }
}