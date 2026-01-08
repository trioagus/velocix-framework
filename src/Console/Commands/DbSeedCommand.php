<?php

namespace Velocix\Console\Commands;

use Velocix\Console\Command;

class DbSeedCommand extends Command
{
    protected $signature = 'db:seed';
    protected $description = 'Seed the database with records';

    public function handle($args = [])
    {
        $class = $this->getOption('class', $args) ?? 'DatabaseSeeder';

        $this->info("Seeding database...");
        $this->line('');

        try {
            $seederClass = "Database\\Seeders\\{$class}";
            
            // Check if seeder class exists
            if (!class_exists($seederClass)) {
                $this->error("Seeder class [{$seederClass}] not found.");
                $this->line('');
                $this->line('Available options:');
                $this->line('  php velocix db:seed');
                $this->line('  php velocix db:seed --class=UserSeeder');
                return;
            }

            // Create and run seeder
            $seeder = new $seederClass();
            $seeder->run();

            $this->line('');
            $this->info('✓ Database seeded successfully!');

        } catch (\Exception $e) {
            $this->line('');
            $this->error('Seeding failed!');
            $this->line('Error: ' . $e->getMessage());
            
            if ($this->hasOption('verbose', $args)) {
                $this->line('');
                $this->line('Stack trace:');
                $this->line($e->getTraceAsString());
            }
        }
    }

    protected function getOption($name, $args)
    {
        foreach ($args as $arg) {
            if (strpos($arg, "--{$name}=") === 0) {
                return substr($arg, strlen("--{$name}="));
            }
        }
        return null;
    }

    protected function hasOption($name, $args)
    {
        return in_array("--{$name}", $args);
    }
}