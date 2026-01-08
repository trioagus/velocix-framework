<?php

namespace Velocix\Console\Commands;

use Velocix\Console\Command;
use Velocix\Database\Connection;
use Velocix\Database\Model;

class MigrateCommand extends Command
{
    protected $signature = 'migrate';
    protected $description = 'Run database migrations';

    public function handle($args = [])
    {
        try {
            $this->info('Running migrations...');

            // Load database config
            $config = require getcwd() . '/config/database.php';
            $defaultConnection = $config['default'] ?? 'mysql';
            $connectionConfig = $config['connections'][$defaultConnection] ?? $config;

            // Create connection
            $connection = Connection::make($connectionConfig);
            Model::setConnection($connection);

            // Create migrations table if not exists
            $this->createMigrationsTable($connection);

            // Get all migration files
            $migrationsPath = getcwd() . '/database/migrations';
            
            if (!is_dir($migrationsPath)) {
                $this->error('Migrations directory not found!');
                return;
            }

            $files = glob($migrationsPath . '/*.php');
            
            if (empty($files)) {
                $this->info('No migrations to run.');
                return;
            }

            // Get already run migrations
            $ranMigrations = $this->getRanMigrations($connection);

            $count = 0;
            foreach ($files as $file) {
                $migrationName = basename($file, '.php');

                // Skip if already run
                if (in_array($migrationName, $ranMigrations)) {
                    continue;
                }

                // Run migration
                $this->line("Migrating: {$migrationName}");
                
                require_once $file;
                
                // Get class name from file
                $className = $this->getClassNameFromFile($file);
                
                if (!class_exists($className)) {
                    $this->error("Migration class {$className} not found in {$file}");
                    continue;
                }

                $migration = new $className($connection);
                $migration->up();

                // Record migration
                $this->recordMigration($connection, $migrationName);

                $this->info("Migrated:  {$migrationName}");
                $count++;
            }

            if ($count === 0) {
                $this->info('Nothing to migrate.');
            } else {
                $this->info("\nMigrated {$count} migration(s) successfully!");
            }

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            if (env('APP_DEBUG', false)) {
                $this->line("\nStack trace:");
                $this->line($e->getTraceAsString());
            }
        }
    }

    protected function createMigrationsTable($connection)
    {
        $driver = $connection->getDriver();
        
        switch ($driver) {
            case 'mysql':
                $sql = "CREATE TABLE IF NOT EXISTS migrations (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL,
                    batch INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                break;
                
            case 'pgsql':
                $sql = "CREATE TABLE IF NOT EXISTS migrations (
                    id SERIAL PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL,
                    batch INTEGER NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                break;
                
            case 'sqlite':
                $sql = "CREATE TABLE IF NOT EXISTS migrations (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    migration TEXT NOT NULL,
                    batch INTEGER NOT NULL,
                    created_at TEXT DEFAULT CURRENT_TIMESTAMP
                )";
                break;
        }

        $connection->query($sql);
    }

    protected function getRanMigrations($connection)
    {
        try {
            $stmt = $connection->query("SELECT migration FROM migrations ORDER BY batch, id");
            return array_column($stmt->fetchAll(), 'migration');
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function recordMigration($connection, $migrationName)
    {
        // Get current batch number
        $stmt = $connection->query("SELECT MAX(batch) as max_batch FROM migrations");
        $result = $stmt->fetch();
        $batch = ($result['max_batch'] ?? 0) + 1;

        $connection->query(
            "INSERT INTO migrations (migration, batch) VALUES (?, ?)",
            [$migrationName, $batch]
        );
    }

    protected function getClassNameFromFile($file)
    {
        $content = file_get_contents($file);
        
        // Extract class name
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }
}