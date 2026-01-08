<?php

namespace Velocix\Database;

use PDO;
use PDOException;

class Connection
{
    protected $pdo;
    protected static $instance;
    protected $driver;

    protected function __construct($config)
    {
        $this->driver = $config['driver'] ?? 'mysql';
        
        try {
            // PENTING: Auto create database kalau belum ada
            if (in_array($this->driver, ['mysql', 'pgsql', 'postgres', 'postgresql'])) {
                $this->createDatabaseIfNotExists($config);
            }
            
            $dsn = $this->buildDsn($config);
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            if ($this->driver === 'mysql' && !empty($config['charset'])) {
                $options[1002] = "SET NAMES {$config['charset']}";
            }

            $this->pdo = new PDO(
                $dsn,
                $config['username'] ?? null,
                $config['password'] ?? null,
                $options
            );
        } catch (PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    // METHOD BARU - Auto create database
    protected function createDatabaseIfNotExists($config)
    {
        try {
            $driver = $config['driver'] ?? 'mysql';
            $dbName = $config['database'];
            
            if ($driver === 'mysql') {
                // Connect tanpa specify database dulu
                $dsn = sprintf(
                    "mysql:host=%s;port=%s;charset=%s",
                    $config['host'] ?? 'localhost',
                    $config['port'] ?? 3306,
                    $config['charset'] ?? 'utf8mb4'
                );
                
                if (!empty($config['unix_socket'])) {
                    $dsn = sprintf(
                        "mysql:unix_socket=%s;charset=%s",
                        $config['unix_socket'],
                        $config['charset'] ?? 'utf8mb4'
                    );
                }
                
                $pdo = new PDO(
                    $dsn,
                    $config['username'] ?? null,
                    $config['password'] ?? null,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                // Create database if not exists
                $charset = $config['charset'] ?? 'utf8mb4';
                $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';
                $sql = "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET {$charset} COLLATE {$collation}";
                $pdo->exec($sql);
                
            } elseif (in_array($driver, ['pgsql', 'postgres', 'postgresql'])) {
                // Connect ke default postgres database
                $dsn = sprintf(
                    "pgsql:host=%s;port=%s;dbname=postgres",
                    $config['host'] ?? 'localhost',
                    $config['port'] ?? 5432
                );
                
                $pdo = new PDO(
                    $dsn,
                    $config['username'] ?? null,
                    $config['password'] ?? null,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                // Check if database exists
                $stmt = $pdo->query("SELECT 1 FROM pg_database WHERE datname = '{$dbName}'");
                if (!$stmt->fetchColumn()) {
                    $pdo->exec("CREATE DATABASE \"{$dbName}\"");
                }
            }
        } catch (PDOException $e) {
            // Silent fail - user mungkin gak punya CREATE DATABASE privilege
            // Biarkan error asli muncul di connection berikutnya
        }
    }

    protected function buildDsn($config)
    {
        $driver = $config['driver'] ?? 'mysql';

        switch ($driver) {
            case 'mysql':
                $dsn = sprintf(
                    "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                    $config['host'] ?? 'localhost',
                    $config['port'] ?? 3306,
                    $config['database'],
                    $config['charset'] ?? 'utf8mb4'
                );
                
                if (!empty($config['unix_socket'])) {
                    $dsn = sprintf(
                        "mysql:unix_socket=%s;dbname=%s;charset=%s",
                        $config['unix_socket'],
                        $config['database'],
                        $config['charset'] ?? 'utf8mb4'
                    );
                }
                
                return $dsn;

            case 'pgsql':
            case 'postgres':
            case 'postgresql':
                $this->driver = 'pgsql';
                return sprintf(
                    "pgsql:host=%s;port=%s;dbname=%s",
                    $config['host'] ?? 'localhost',
                    $config['port'] ?? 5432,
                    $config['database']
                );

            case 'sqlite':
                $database = $config['database'];
                
                if ($database === ':memory:') {
                    return 'sqlite::memory:';
                }
                
                if ($database[0] !== '/') {
                    $database = getcwd() . '/' . $database;
                }
                
                $dir = dirname($database);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                if (!file_exists($database)) {
                    touch($database);
                    chmod($database, 0644);
                }
                
                return "sqlite:{$database}";

            default:
                throw new \Exception("Unsupported database driver: {$driver}");
        }
    }

    public static function make($config)
    {
        if (!static::$instance) {
            static::$instance = new static($config);
        }
        return static::$instance;
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    public function getDriver()
    {
        return $this->driver;
    }

    public function query($sql, $bindings = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bindings);
            return $stmt;
        } catch (PDOException $e) {
            throw new \Exception("Query failed: " . $e->getMessage() . "\nSQL: " . $sql);
        }
    }

    public function statement($sql, $bindings = [])
    {
        return $this->query($sql, $bindings);
    }

    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    public function commit()
    {
        return $this->pdo->commit();
    }

    public function rollback()
    {
        return $this->pdo->rollBack();
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
}