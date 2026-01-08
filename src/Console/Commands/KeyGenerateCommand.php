<?php

namespace Velocix\Console\Commands;

use Velocix\Console\Command;

class KeyGenerateCommand extends Command
{
    protected $signature = 'key:generate';
    protected $description = 'Generate a new application key';

    public function handle($args = [])
    {
        $key = 'base64:' . base64_encode(random_bytes(32));

        $envFile = '.env';

        if (!file_exists($envFile)) {
            $this->error('.env file not found!');
            $this->line('Please copy .env.example to .env first');
            return;
        }

        $env = file_get_contents($envFile);

        // Replace or add APP_KEY
        if (strpos($env, 'APP_KEY=') !== false) {
            $env = preg_replace('/APP_KEY=.*/', "APP_KEY={$key}", $env);
        } else {
            $env .= "\nAPP_KEY={$key}\n";
        }

        file_put_contents($envFile, $env);

        $this->info('Application key set successfully!');
        $this->line("Key: {$key}");
    }
}