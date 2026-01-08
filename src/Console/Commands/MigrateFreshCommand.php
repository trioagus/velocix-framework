<?php

namespace Velocix\Console\Commands;

use Velocix\Console\Command;

class MigrateFreshCommand extends Command
{
    protected $signature = 'migrate:fresh';
    protected $description = 'Drop all tables and re-run all migrations';

    public function handle($args = [])
    {
        if (!$this->confirm('This will drop all tables. Are you sure?')) {
            $this->info('Migration cancelled.');
            return;
        }

        $this->info('Dropping all tables...');
        $this->dropAllTables();

        $this->line('');
        $this->info('Running migrations...');
        $this->runMigrations();

        // Check if --seed option is provided
        if ($this->hasOption('seed', $args)) {
            $this->line('');
            $this->info('Seeding database...');
            $this->runSeeders();
        }

        $this->line('');
        $this->info('✓ Migration completed successfully!');
    }

    protected function dropAllTables()
    {
        $database = env('DB_NAME', 'database.sqlite');
        $dbPath = getcwd() . '/database/' . $database;

        if (file_exists($dbPath)) {
            unlink($dbPath);
            $this->line('  Database file deleted');
        }

        // Recreate empty database
        touch($dbPath);
        $this->line('  New database file created');
    }

    protected function runMigrations()
    {
        $migrationPath = getcwd() . '/database/migrations';
        
        if (!is_dir($migrationPath)) {
            $this->error('No migrations directory found.');
            return;
        }

        $files = glob($migrationPath . '/*.php');
        
        if (empty($files)) {
            $this->info('No migrations to run.');
            return;
        }

        foreach ($files as $file) {
            require_once $file;
            
            $className = $this->getMigrationClassName($file);
            
            if (class_exists($className)) {
                $migration = new $className();
                
                echo "  Migrating: " . basename($file) . "\n";
                $migration->up();
                echo "  Migrated: " . basename($file) . "\n";
            }
        }
    }

    protected function runSeeders()
    {
        $seederClass = "Database\\Seeders\\DatabaseSeeder";
        
        if (class_exists($seederClass)) {
            $seeder = new $seederClass();
            $seeder->run();
        } else {
            $this->warn('  DatabaseSeeder not found. Skipping seeding.');
        }
    }

    protected function getMigrationClassName($file)
    {
        $filename = basename($file, '.php');
        $parts = explode('_', $filename);
        array_shift($parts); // Remove timestamp
        
        return implode('', array_map('ucfirst', $parts));
    }

    protected function confirm($question)
    {
        echo "{$question} (yes/no): ";
        $answer = trim(fgets(STDIN));
        return in_array(strtolower($answer), ['yes', 'y']);
    }

    protected function hasOption($name, $args)
    {
        return in_array("--{$name}", $args);
    }
}