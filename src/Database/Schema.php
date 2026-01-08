<?php

namespace Velocix\Database;

class Schema
{
    protected $connection;
    protected $table;
    protected $columns = [];
    protected $driver;

    public function __construct(Connection $connection, $table)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->driver = $this->getDriver();
    }

    protected function getDriver()
    {
        return $this->connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    public function id()
    {
        switch ($this->driver) {
            case 'mysql':
                $this->columns[] = "id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY";
                break;
            case 'pgsql':
                $this->columns[] = "id BIGSERIAL PRIMARY KEY";
                break;
            case 'sqlite':
                $this->columns[] = "id INTEGER PRIMARY KEY AUTOINCREMENT";
                break;
        }
        return $this;
    }

    public function uuid($name = 'id')
    {
        switch ($this->driver) {
            case 'mysql':
                $this->columns[] = "{$name} CHAR(36)";
                if ($name === 'id') {
                    $this->columns[count($this->columns) - 1] .= " PRIMARY KEY";
                }
                break;
            case 'pgsql':
                $this->columns[] = "{$name} UUID";
                if ($name === 'id') {
                    $this->columns[count($this->columns) - 1] .= " PRIMARY KEY";
                }
                break;
            case 'sqlite':
                $this->columns[] = "{$name} TEXT";
                if ($name === 'id') {
                    $this->columns[count($this->columns) - 1] .= " PRIMARY KEY";
                }
                break;
        }
        return $this;
    }

    public function ulid($name = 'id')
    {
        switch ($this->driver) {
            case 'mysql':
                $this->columns[] = "{$name} CHAR(26)";
                if ($name === 'id') {
                    $this->columns[count($this->columns) - 1] .= " PRIMARY KEY";
                }
                break;
            case 'pgsql':
            case 'sqlite':
                $this->columns[] = "{$name} TEXT";
                if ($name === 'id') {
                    $this->columns[count($this->columns) - 1] .= " PRIMARY KEY";
                }
                break;
        }
        return $this;
    }

    public function string($name, $length = 255)
    {
        $this->columns[] = "{$name} VARCHAR({$length})";
        return $this;
    }

    public function text($name)
    {
        $this->columns[] = "{$name} TEXT";
        return $this;
    }

    public function integer($name)
    {
        switch ($this->driver) {
            case 'mysql':
                $this->columns[] = "{$name} INT";
                break;
            case 'pgsql':
                $this->columns[] = "{$name} INTEGER";
                break;
            case 'sqlite':
                $this->columns[] = "{$name} INTEGER";
                break;
        }
        return $this;
    }

    public function bigInteger($name)
    {
        switch ($this->driver) {
            case 'mysql':
                $this->columns[] = "{$name} BIGINT";
                break;
            case 'pgsql':
                $this->columns[] = "{$name} BIGINT";
                break;
            case 'sqlite':
                $this->columns[] = "{$name} INTEGER";
                break;
        }
        return $this;
    }

    public function decimal($name, $precision = 8, $scale = 2)
    {
        $this->columns[] = "{$name} DECIMAL({$precision}, {$scale})";
        return $this;
    }

    public function boolean($name)
    {
        switch ($this->driver) {
            case 'mysql':
                $this->columns[] = "{$name} BOOLEAN DEFAULT FALSE";
                break;
            case 'pgsql':
                $this->columns[] = "{$name} BOOLEAN DEFAULT FALSE";
                break;
            case 'sqlite':
                $this->columns[] = "{$name} INTEGER DEFAULT 0";
                break;
        }
        return $this;
    }

    public function json($name)
    {
        switch ($this->driver) {
            case 'mysql':
                $this->columns[] = "{$name} JSON";
                break;
            case 'pgsql':
                $this->columns[] = "{$name} JSONB";
                break;
            case 'sqlite':
                $this->columns[] = "{$name} TEXT";
                break;
        }
        return $this;
    }

    public function enum($name, $values)
    {
        $valueList = "'" . implode("','", $values) . "'";
        
        switch ($this->driver) {
            case 'mysql':
                $this->columns[] = "{$name} ENUM({$valueList})";
                break;
            case 'pgsql':
                // PostgreSQL requires custom type, use VARCHAR with CHECK constraint
                $this->columns[] = "{$name} VARCHAR(255) CHECK ({$name} IN ({$valueList}))";
                break;
            case 'sqlite':
                $this->columns[] = "{$name} TEXT CHECK ({$name} IN ({$valueList}))";
                break;
        }
        return $this;
    }

    public function timestamp($name)
    {
        switch ($this->driver) {
            case 'mysql':
                $this->columns[] = "{$name} TIMESTAMP NULL";
                break;
            case 'pgsql':
                $this->columns[] = "{$name} TIMESTAMP";
                break;
            case 'sqlite':
                $this->columns[] = "{$name} TEXT";
                break;
        }
        return $this;
    }

    public function timestamps()
    {
        switch ($this->driver) {
            case 'mysql':
                $this->columns[] = "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
                $this->columns[] = "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
                break;
            case 'pgsql':
                $this->columns[] = "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
                $this->columns[] = "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
                break;
            case 'sqlite':
                $this->columns[] = "created_at TEXT DEFAULT CURRENT_TIMESTAMP";
                $this->columns[] = "updated_at TEXT DEFAULT CURRENT_TIMESTAMP";
                break;
        }
        return $this;
    }

    public function nullable()
    {
        $lastIndex = count($this->columns) - 1;
        if ($lastIndex >= 0) {
            // Remove any existing NOT NULL and add NULL
            $this->columns[$lastIndex] = str_replace('NOT NULL', '', $this->columns[$lastIndex]);
            if (strpos($this->columns[$lastIndex], 'NULL') === false) {
                $this->columns[$lastIndex] .= " NULL";
            }
        }
        return $this;
    }

    public function unique()
    {
        $lastIndex = count($this->columns) - 1;
        if ($lastIndex >= 0) {
            $this->columns[$lastIndex] .= " UNIQUE";
        }
        return $this;
    }

    public function default($value)
    {
        $lastIndex = count($this->columns) - 1;
        if ($lastIndex >= 0) {
            if (is_string($value)) {
                $value = "'{$value}'";
            } elseif (is_bool($value)) {
                $value = $value ? 'TRUE' : 'FALSE';
            }
            $this->columns[$lastIndex] .= " DEFAULT {$value}";
        }
        return $this;
    }

    public function unsigned()
    {
        $lastIndex = count($this->columns) - 1;
        if ($lastIndex >= 0 && $this->driver === 'mysql') {
            $this->columns[$lastIndex] .= " UNSIGNED";
        }
        return $this;
    }

    public function index()
    {
        // Will be handled in execute() method
        return $this;
    }

    public function execute()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (" . 
               implode(', ', $this->columns) . 
               ")";
        
        // Add database-specific options
        switch ($this->driver) {
            case 'mysql':
                $sql .= " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                break;
        }
        
        $this->connection->query($sql);
    }
}