<?php

namespace Velocix\Database;

class QueryBuilder
{
    protected $connection;
    protected $table;
    protected $wheres = [];
    protected $bindings = [];
    protected $selects = ['*'];
    protected $orders = [];
    protected $limit;
    protected $offset;
    protected $joins = [];
    protected $groupBy = [];
    protected $having = [];

    // Whitelist untuk ORDER BY dan column names
    protected $allowedOperators = ['=', '!=', '<>', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'IS NULL', 'IS NOT NULL'];
    protected $allowedDirections = ['ASC', 'DESC'];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function table($table)
    {
        // Sanitize table name - only allow alphanumeric and underscore
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new \InvalidArgumentException("Invalid table name: {$table}");
        }
        
        $this->table = $table;
        return $this;
    }

    public function select(...$columns)
    {
        // Sanitize column names
        foreach ($columns as $column) {
            if (!$this->isValidColumnName($column)) {
                throw new \InvalidArgumentException("Invalid column name: {$column}");
            }
        }
        
        $this->selects = $columns;
        return $this;
    }

    public function where($column, $operator, $value = null)
    {
        if (is_null($value)) {
            $value = $operator;
            $operator = '=';
        }

        // Validate operator
        $operator = strtoupper(trim($operator));
        if (!in_array($operator, $this->allowedOperators)) {
            throw new \InvalidArgumentException("Invalid operator: {$operator}");
        }

        // Validate column name
        if (!$this->isValidColumnName($column)) {
            throw new \InvalidArgumentException("Invalid column name: {$column}");
        }

        // Use prepared statements - NO direct value interpolation
        if ($operator === 'IN' || $operator === 'NOT IN') {
            if (!is_array($value)) {
                throw new \InvalidArgumentException("Value for IN operator must be an array");
            }
            $placeholders = implode(', ', array_fill(0, count($value), '?'));
            $this->wheres[] = "{$column} {$operator} ({$placeholders})";
            $this->bindings = array_merge($this->bindings, $value);
        } elseif ($operator === 'IS NULL' || $operator === 'IS NOT NULL') {
            $this->wheres[] = "{$column} {$operator}";
        } else {
            $this->wheres[] = "{$column} {$operator} ?";
            $this->bindings[] = $value;
        }

        return $this;
    }

    public function whereIn($column, array $values)
    {
        return $this->where($column, 'IN', $values);
    }

    public function whereNotIn($column, array $values)
    {
        return $this->where($column, 'NOT IN', $values);
    }

    public function whereNull($column)
    {
        if (!$this->isValidColumnName($column)) {
            throw new \InvalidArgumentException("Invalid column name: {$column}");
        }
        $this->wheres[] = "{$column} IS NULL";
        return $this;
    }

    public function whereNotNull($column)
    {
        if (!$this->isValidColumnName($column)) {
            throw new \InvalidArgumentException("Invalid column name: {$column}");
        }
        $this->wheres[] = "{$column} IS NOT NULL";
        return $this;
    }

    public function orderBy($column, $direction = 'ASC')
    {
        // Validate column
        if (!$this->isValidColumnName($column)) {
            throw new \InvalidArgumentException("Invalid column name: {$column}");
        }

        // Validate direction
        $direction = strtoupper(trim($direction));
        if (!in_array($direction, $this->allowedDirections)) {
            throw new \InvalidArgumentException("Invalid sort direction: {$direction}");
        }

        $this->orders[] = "{$column} {$direction}";
        return $this;
    }

    public function limit($limit)
    {
        // Validate limit is positive integer
        if (!is_numeric($limit) || $limit < 0) {
            throw new \InvalidArgumentException("Limit must be a positive integer");
        }
        
        $this->limit = (int) $limit;
        return $this;
    }

    public function offset($offset)
    {
        // Validate offset is positive integer
        if (!is_numeric($offset) || $offset < 0) {
            throw new \InvalidArgumentException("Offset must be a positive integer");
        }
        
        $this->offset = (int) $offset;
        return $this;
    }

    public function get()
    {
        $sql = $this->buildSelectSql();
        $stmt = $this->connection->query($sql, $this->bindings);
        return $stmt->fetchAll();
    }

    public function first()
    {
        $this->limit(1);
        $result = $this->get();
        return $result[0] ?? null;
    }

    public function find($id)
    {
        return $this->where('id', $id)->first();
    }

    public function count()
    {
        $originalSelects = $this->selects;
        $this->selects = ['COUNT(*) as total'];
        
        $sql = $this->buildSelectSql();
        $stmt = $this->connection->query($sql, $this->bindings);
        $result = $stmt->fetch();
        
        $this->selects = $originalSelects;
        
        return (int) ($result->total ?? 0);
    }

    public function insert($data)
    {
        // Validate data
        if (empty($data) || !is_array($data)) {
            throw new \InvalidArgumentException("Insert data must be a non-empty array");
        }

        // Validate column names
        foreach (array_keys($data) as $column) {
            if (!$this->isValidColumnName($column)) {
                throw new \InvalidArgumentException("Invalid column name: {$column}");
            }
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $this->connection->query($sql, array_values($data));
        
        return $this->connection->getPdo()->lastInsertId();
    }

    public function update($data)
    {
        // Validate data
        if (empty($data) || !is_array($data)) {
            throw new \InvalidArgumentException("Update data must be a non-empty array");
        }

        // Validate column names
        foreach (array_keys($data) as $column) {
            if (!$this->isValidColumnName($column)) {
                throw new \InvalidArgumentException("Invalid column name: {$column}");
            }
        }

        $sets = [];
        $bindings = [];
        
        foreach ($data as $column => $value) {
            $sets[] = "{$column} = ?";
            $bindings[] = $value;
        }
        
        $bindings = array_merge($bindings, $this->bindings);
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }
        
        $stmt = $this->connection->query($sql, $bindings);
        return $stmt->rowCount();
    }

    public function delete()
    {
        $sql = "DELETE FROM {$this->table}";
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }
        
        $stmt = $this->connection->query($sql, $this->bindings);
        return $stmt->rowCount();
    }

    protected function buildSelectSql()
    {
        $sql = "SELECT " . implode(', ', $this->selects) . " FROM {$this->table}";
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }
        
        if (!empty($this->orders)) {
            $sql .= " ORDER BY " . implode(', ', $this->orders);
        }
        
        if ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        if ($this->offset) {
            $sql .= " OFFSET {$this->offset}";
        }
        
        return $sql;
    }

    /**
     * Validate column name to prevent SQL injection
     * Allow: letters, numbers, underscore, dot (for table.column)
     */
    protected function isValidColumnName($column)
    {
        // Allow * for SELECT *
        if ($column === '*') {
            return true;
        }

        // Allow table.column format
        // Allow AS aliases
        // Allow COUNT(*), SUM(), etc
        $pattern = '/^[a-zA-Z0-9_\.]+(\s+as\s+[a-zA-Z0-9_]+)?$|^(COUNT|SUM|AVG|MIN|MAX)\([a-zA-Z0-9_\.\*]+\)(\s+as\s+[a-zA-Z0-9_]+)?$/i';
        
        return preg_match($pattern, trim($column)) === 1;
    }

    /**
     * Reset query builder state
     */
    public function reset()
    {
        $this->wheres = [];
        $this->bindings = [];
        $this->selects = ['*'];
        $this->orders = [];
        $this->limit = null;
        $this->offset = null;
        
        return $this;
    }
}