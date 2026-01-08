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
                
                // Add unix_socket if provided
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
                $this->driver = 'pgsql'; // Normalize driver name
                return sprintf(
                    "pgsql:host=%s;port=%s;dbname=%s",
                    $config['host'] ?? 'localhost',
                    $config['port'] ?? 5432,
                    $config['database']
                );

            case 'sqlite':
                // Support both file path and :memory:
                $database = $config['database'];
                
                if ($database === ':memory:') {
                    return 'sqlite::memory:';
                }
                
                // If not absolute path, make it relative to project root
                if ($database[0] !== '/') {
                    $database = getcwd() . '/' . $database;
                }
                
                // Create directory if it doesn't exist
                $dir = dirname($database);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
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