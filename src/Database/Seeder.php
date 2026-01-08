<?php

namespace Velocix\Database;

abstract class Seeder
{
    /**
     * Run the database seeds
     */
    abstract public function run();

    /**
     * Call another seeder
     */
    public function call($seederClass)
    {
        $seeder = new $seederClass();
        
        echo "Seeding: " . $seederClass . "\n";
        
        $seeder->run();
        
        echo "Seeded: " . $seederClass . "\n";
    }

    /**
     * Truncate table before seeding (Universal untuk semua DB)
     */
    protected function truncate($table)
    {
        $driver = $this->getDriver();
        
        switch ($driver) {
            case 'mysql':
                // MySQL: TRUNCATE atau DELETE with reset auto increment
                try {
                    $this->db()->statement("TRUNCATE TABLE `{$table}`");
                } catch (\Exception $e) {
                    // Fallback jika foreign key constraint
                    $this->db()->statement("SET FOREIGN_KEY_CHECKS = 0");
                    $this->db()->statement("TRUNCATE TABLE `{$table}`");
                    $this->db()->statement("SET FOREIGN_KEY_CHECKS = 1");
                }
                break;
                
            case 'pgsql':
            case 'postgresql':
                // PostgreSQL: TRUNCATE with CASCADE
                $this->db()->statement("TRUNCATE TABLE \"{$table}\" RESTART IDENTITY CASCADE");
                break;
                
            case 'sqlite':
                // SQLite: DELETE + reset sequence
                $this->db()->statement("DELETE FROM {$table}");
                $this->db()->statement("DELETE FROM sqlite_sequence WHERE name='{$table}'");
                break;
                
            case 'sqlsrv':
            case 'mssql':
                // SQL Server
                $this->db()->statement("TRUNCATE TABLE [{$table}]");
                break;
                
            default:
                // Default fallback
                $this->db()->statement("DELETE FROM {$table}");
        }
    }

    /**
     * Get database driver name
     */
    protected function getDriver()
    {
        $driver = $this->env('DB_CONNECTION', 'sqlite');
        return strtolower($driver);
    }

    /**
     * Disable foreign key checks (untuk truncate dengan relasi)
     */
    protected function disableForeignKeyChecks()
    {
        $driver = $this->getDriver();
        
        switch ($driver) {
            case 'mysql':
                $this->db()->statement("SET FOREIGN_KEY_CHECKS = 0");
                break;
            case 'pgsql':
            case 'postgresql':
                $this->db()->statement("SET CONSTRAINTS ALL DEFERRED");
                break;
            case 'sqlite':
                $this->db()->statement("PRAGMA foreign_keys = OFF");
                break;
            case 'sqlsrv':
            case 'mssql':
                // SQL Server uses NOCHECK
                break;
        }
    }

    /**
     * Enable foreign key checks
     */
    protected function enableForeignKeyChecks()
    {
        $driver = $this->getDriver();
        
        switch ($driver) {
            case 'mysql':
                $this->db()->statement("SET FOREIGN_KEY_CHECKS = 1");
                break;
            case 'pgsql':
            case 'postgresql':
                $this->db()->statement("SET CONSTRAINTS ALL IMMEDIATE");
                break;
            case 'sqlite':
                $this->db()->statement("PRAGMA foreign_keys = ON");
                break;
        }
    }

    /**
     * Get database connection
     */
    protected function db()
    {
        static $connection;

        if (!$connection) {
            // Get database config from config/database.php
            $default = $this->config('database.default', 'mysql');
            $config = $this->config("database.connections.{$default}");

            if (!$config) {
                throw new \Exception("Database connection [{$default}] not configured.");
            }

            // Use Connection::make() instead of new Connection()
            $connection = \Velocix\Database\Connection::make($config);
        }

        return $connection;
    }

    /**
     * Get config value
     */
    protected function config($key, $default = null)
    {
        static $config;

        if ($config === null) {
            $config = [];
            $configPath = getcwd() . '/config';

            if (is_dir($configPath)) {
                $files = glob($configPath . '/*.php');
                foreach ($files as $file) {
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

    /**
     * Get environment variable
     */
    protected function env($key, $default = null)
    {
        static $env;

        if ($env === null) {
            $env = [];
            $envFile = getcwd() . '/.env';

            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) {
                        continue;
                    }

                    if (strpos($line, '=') !== false) {
                        list($name, $value) = explode('=', $line, 2);
                        $name = trim($name);
                        $value = trim($value);
                        
                        // Remove quotes
                        $value = trim($value, '"\'');
                        
                        $env[$name] = $value;
                    }
                }
            }
        }

        return $env[$key] ?? $default;
    }

    /**
     * Insert data into table
     */
    protected function insert($table, array $data)
    {
        // Build insert query
        $columns = array_keys($data);
        $columnNames = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columnNames}) VALUES ({$placeholders})";
        
        return $this->db()->query($sql, array_values($data));
    }

    /**
     * Create multiple records
     */
    protected function create($table, array $records)
    {
        foreach ($records as $record) {
            $this->insert($table, $record);
        }
    }

    /**
     * Batch insert (lebih cepat untuk banyak data)
     */
    protected function batchInsert($table, array $records, $batchSize = 100)
    {
        $chunks = array_chunk($records, $batchSize);
        
        foreach ($chunks as $chunk) {
            // Build bulk insert query
            $columns = array_keys($chunk[0]);
            $columnNames = implode(', ', $columns);
            
            $values = [];
            $bindings = [];
            
            foreach ($chunk as $record) {
                $placeholders = array_fill(0, count($record), '?');
                $values[] = '(' . implode(', ', $placeholders) . ')';
                $bindings = array_merge($bindings, array_values($record));
            }
            
            $sql = "INSERT INTO {$table} ({$columnNames}) VALUES " . implode(', ', $values);
            $this->db()->query($sql, $bindings);
        }
    }

    /**
     * Generate random data helpers
     */
    protected function randomName()
    {
        $names = ['John', 'Jane', 'Alice', 'Bob', 'Charlie', 'Diana', 'Eve', 'Frank', 'Grace', 'Henry', 
                  'Ivy', 'Jack', 'Kelly', 'Liam', 'Mia', 'Noah', 'Olivia', 'Peter', 'Quinn', 'Rose'];
        return $names[array_rand($names)];
    }

    protected function randomEmail()
    {
        $domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'example.com', 'test.com'];
        return strtolower($this->randomName()) . rand(100, 999) . '@' . $domains[array_rand($domains)];
    }

    protected function randomText($words = 10)
    {
        $lorem = 'Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua ut enim ad minim veniam quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur';
        $words_array = explode(' ', $lorem);
        shuffle($words_array);
        return implode(' ', array_slice($words_array, 0, $words));
    }

    protected function randomParagraph()
    {
        return $this->randomText(rand(20, 50));
    }

    protected function randomDate($startDate = '-1 year', $endDate = 'now')
    {
        $timestamp = rand(strtotime($startDate), strtotime($endDate));
        return date('Y-m-d H:i:s', $timestamp);
    }

    protected function randomBoolean()
    {
        return (bool) rand(0, 1);
    }

    protected function randomNumber($min = 1, $max = 100)
    {
        return rand($min, $max);
    }

    protected function randomFloat($min = 0, $max = 1000, $decimals = 2)
    {
        $value = $min + mt_rand() / mt_getrandmax() * ($max - $min);
        return round($value, $decimals);
    }

    protected function randomPhone()
    {
        return sprintf('+62 8%d-%d-%d', 
            rand(10, 99), 
            rand(1000, 9999), 
            rand(1000, 9999)
        );
    }

    protected function randomUrl()
    {
        $domains = ['example.com', 'test.com', 'demo.com', 'sample.com'];
        return 'https://www.' . $domains[array_rand($domains)] . '/' . strtolower($this->randomName());
    }

    protected function randomSlug()
    {
        return strtolower($this->randomName()) . '-' . rand(100, 999);
    }

    protected function randomStatus()
    {
        $statuses = ['active', 'inactive', 'pending', 'completed'];
        return $statuses[array_rand($statuses)];
    }
}