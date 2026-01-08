<?php

namespace Velocix\Database;

abstract class Migration
{
    protected $connection;
    
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    abstract public function up();
    abstract public function down();

    protected function createTable($table, $callback)
    {
        $schema = new Schema($this->connection, $table);
        $callback($schema);
        $schema->execute();
    }

    protected function dropTable($table)
    {
        $sql = "DROP TABLE IF EXISTS {$table}";
        $this->connection->query($sql);
    }
}
