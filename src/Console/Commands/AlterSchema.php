<?php

namespace Velocix\Database;

class AlterSchema
{
    protected $connection;
    protected $table;
    protected $commands = [];
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

    // Add new column methods (same as Schema class)
    public function string($name, $length = 255)
    {
        $this->commands[] = [
            'type' => 'add',
            'sql' => "{$name} VARCHAR({$length})"
        ];
        return $this;
    }

    public function text($name)
    {
        $this->commands[] = [
            'type' => 'add',
            'sql' => "{$name} TEXT"
        ];
        return $this;
    }

    public function integer($name)
    {
        $sql = $this->driver === 'mysql' ? "{$name} INT" : "{$name} INTEGER";
        $this->commands[] = [
            'type' => 'add',
            'sql' => $sql
        ];
        return $this;
    }

    public function bigInteger($name)
    {
        switch ($this->driver) {
            case 'mysql':
                $sql = "{$name} BIGINT";
                break;
            case 'pgsql':
                $sql = "{$name} BIGINT";
                break;
            case 'sqlite':
                $sql = "{$name} INTEGER";
                break;
        }
        $this->commands[] = [
            'type' => 'add',
            'sql' => $sql
        ];
        return $this;
    }

    public function decimal($name, $precision = 8, $scale = 2)
    {
        $this->commands[] = [
            'type' => 'add',
            'sql' => "{$name} DECIMAL({$precision}, {$scale})"
        ];
        return $this;
    }

    public function boolean($name)
    {
        switch ($this->driver) {
            case 'mysql':
                $sql = "{$name} BOOLEAN DEFAULT FALSE";
                break;
            case 'pgsql':
                $sql = "{$name} BOOLEAN DEFAULT FALSE";
                break;
            case 'sqlite':
                $sql = "{$name} INTEGER DEFAULT 0";
                break;
        }
        
        $this->commands[] = [
            'type' => 'add',
            'sql' => $sql
        ];
        return $this;
    }

    public function timestamp($name)
    {
        switch ($this->driver) {
            case 'mysql':
                $sql = "{$name} TIMESTAMP NULL";
                break;
            case 'pgsql':
                $sql = "{$name} TIMESTAMP";
                break;
            case 'sqlite':
                $sql = "{$name} TEXT";
                break;
        }
        
        $this->commands[] = [
            'type' => 'add',
            'sql' => $sql
        ];
        return $this;
    }

    public function enum($name, $values)
    {
        $valueList = "'" . implode("','", $values) . "'";
        
        switch ($this->driver) {
            case 'mysql':
                $sql = "{$name} ENUM({$valueList})";
                break;
            case 'pgsql':
                // PostgreSQL uses CHECK constraint for enum-like behavior
                $sql = "{$name} VARCHAR(255) CHECK ({$name} IN ({$valueList}))";
                break;
            case 'sqlite':
                $sql = "{$name} TEXT CHECK ({$name} IN ({$valueList}))";
                break;
        }
        
        $this->commands[] = [
            'type' => 'add',
            'sql' => $sql
        ];
        return $this;
    }

    public function json($name)
    {
        switch ($this->driver) {
            case 'mysql':
                $sql = "{$name} JSON";
                break;
            case 'pgsql':
                $sql = "{$name} JSONB";
                break;
            case 'sqlite':
                $sql = "{$name} TEXT";
                break;
        }
        
        $this->commands[] = [
            'type' => 'add',
            'sql' => $sql
        ];
        return $this;
    }

    public function uuid($name)
    {
        switch ($this->driver) {
            case 'mysql':
                $sql = "{$name} CHAR(36)";
                break;
            case 'pgsql':
                $sql = "{$name} UUID";
                break;
            case 'sqlite':
                $sql = "{$name} TEXT";
                break;
        }
        
        $this->commands[] = [
            'type' => 'add',
            'sql' => $sql
        ];
        return $this;
    }

    public function ulid($name)
    {
        switch ($this->driver) {
            case 'mysql':
                $sql = "{$name} CHAR(26)";
                break;
            case 'pgsql':
            case 'sqlite':
                $sql = "{$name} TEXT";
                break;
        }
        
        $this->commands[] = [
            'type' => 'add',
            'sql' => $sql
        ];
        return $this;
    }

    // Modifiers
    public function nullable()
    {
        $lastIndex = count($this->commands) - 1;
        if ($lastIndex >= 0 && $this->commands[$lastIndex]['type'] === 'add') {
            $this->commands[$lastIndex]['sql'] = str_replace('NOT NULL', '', $this->commands[$lastIndex]['sql']);
            if (strpos($this->commands[$lastIndex]['sql'], 'NULL') === false) {
                $this->commands[$lastIndex]['sql'] .= " NULL";
            }
        }
        return $this;
    }

    public function default($value)
    {
        $lastIndex = count($this->commands) - 1;
        if ($lastIndex >= 0 && $this->commands[$lastIndex]['type'] === 'add') {
            if (is_string($value)) {
                $value = "'{$value}'";
            } elseif (is_bool($value)) {
                $value = $value ? 'TRUE' : 'FALSE';
            }
            $this->commands[$lastIndex]['sql'] .= " DEFAULT {$value}";
        }
        return $this;
    }

    public function unique()
    {
        $lastIndex = count($this->commands) - 1;
        if ($lastIndex >= 0 && $this->commands[$lastIndex]['type'] === 'add') {
            $this->commands[$lastIndex]['sql'] .= " UNIQUE";
        }
        return $this;
    }

    public function unsigned()
    {
        $lastIndex = count($this->commands) - 1;
        if ($lastIndex >= 0 && $this->commands[$lastIndex]['type'] === 'add' && $this->driver === 'mysql') {
            $this->commands[$lastIndex]['sql'] .= " UNSIGNED";
        }
        return $this;
    }

    // Drop column
    public function dropColumn($name)
    {
        $this->commands[] = [
            'type' => 'drop',
            'column' => $name
        ];
        return $this;
    }

    // Execute all commands
    public function execute()
    {
        foreach ($this->commands as $command) {
            if ($command['type'] === 'add') {
                $sql = "ALTER TABLE {$this->table} ADD COLUMN {$command['sql']}";
                $this->connection->getPdo()->exec($sql);
            } elseif ($command['type'] === 'drop') {
                // Note: SQLite before 3.35.0 doesn't support DROP COLUMN
                if ($this->driver === 'sqlite') {
                    $version = $this->connection->getPdo()->query('SELECT sqlite_version()')->fetchColumn();
                    if (version_compare($version, '3.35.0', '<')) {
                        throw new \Exception("SQLite version {$version} does not support DROP COLUMN. Requires 3.35.0 or higher.");
                    }
                }
                
                $sql = "ALTER TABLE {$this->table} DROP COLUMN {$command['column']}";
                $this->connection->getPdo()->exec($sql);
            }
        }
    }
}