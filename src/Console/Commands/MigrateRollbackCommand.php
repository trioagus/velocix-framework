<?php

namespace Velocix\Console\Commands;

use Velocix\Console\Command;
use Velocix\Database\Connection;

class MigrateRollbackCommand extends Command
{
    protected $signature = 'migrate:rollback';
    protected $description = 'Rollback the last database migration';

    public function handle($args = [])
    {
        $this->info('Rolling back migrations...');
        
        $config = require 'config/database.php';
        $connection = Connection::make($config);

        $lastBatch = $this->getLastBatch($connection);
        
        if (!$lastBatch) {
            $this->warn('Nothing to rollback');
            return;
        }

        $migrations = $this->getMigrationsFromBatch($connection, $lastBatch);

        $rolledBack = 0;
        foreach (array_reverse($migrations) as $migration) {
            $file = $this->findMigrationFile($migration);
            
            if (!$file) {
                $this->error("Migration file not found: {$migration}");
                continue;
            }

            require_once $file;
            
            $className = $this->getClassName($migration);
            $instance = new $className($connection);
            
            try {
                $instance->down();
                $this->removeFromMigrations($connection, $migration);
                $this->info("✓ Rolled back: {$migration}");
                $rolledBack++;
            } catch (\Exception $e) {
                $this->error("✗ Failed to rollback: {$migration}");
                $this->error("  Error: " . $e->getMessage());
                break;
            }
        }

        $this->info("\nRollback completed! ({$rolledBack} migrations)");
    }

    protected function getLastBatch($connection)
    {
        $stmt = $connection->query("SELECT MAX(batch) as batch FROM migrations");
        $result = $stmt->fetch();
        return $result ? $result['batch'] : null;
    }

    protected function getMigrationsFromBatch($connection, $batch)
    {
        $stmt = $connection->query(
            "SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC",
            [$batch]
        );
        
        $migrations = [];
        while ($row = $stmt->fetch()) {
            $migrations[] = $row['migration'];
        }
        
        return $migrations;
    }

    protected function findMigrationFile($migration)
    {
        $files = glob('database/migrations/*.php');
        
        foreach ($files as $file) {
            if (basename($file, '.php') === $migration) {
                return $file;
            }
        }
        
        return null;
    }

    protected function removeFromMigrations($connection, $migration)
    {
        $connection->query(
            "DELETE FROM migrations WHERE migration = ?",
            [$migration]
        );
    }

    protected function getClassName($migration)
    {
        $parts = explode('_', $migration);
        array_shift($parts); // year
        array_shift($parts); // month
        array_shift($parts); // day
        array_shift($parts); // time
        return implode('', array_map('ucfirst', $parts));
    }
}