<?php

namespace Velocix\Console\Commands;

use Velocix\Console\Command;

class MakeMigrationCommand extends Command
{
    protected $signature = 'make:migration';
    protected $description = 'Create a new migration file';

    public function handle($args = [])
    {
        if (empty($args[0])) {
            $this->error('Migration name is required');
            $this->line('Usage: php velocix make:migration create_users_table');
            $this->line('       php velocix make:migration create_users_table --schema="name:string,email:string:unique,age:integer:nullable"');
            return;
        }

        $this->args = $args;
        $name = $args[0];
        $timestamp = date('Y_m_d_His');
        $className = $this->getClassName($name);
        $tableName = $this->getTableName($name);
        $filename = "database/migrations/{$timestamp}_{$name}.php";

        if (!is_dir('database/migrations')) {
            mkdir('database/migrations', 0755, true);
        }

        $stub = $this->getStub($className, $tableName, $name);
        file_put_contents($filename, $stub);
        
        $this->info("Migration created successfully: {$filename}");
    }

    protected $args = [];

    protected function getClassName($name)
    {
        // create_users_table -> CreateUsersTable
        // add_email_to_users -> AddEmailToUsers
        return str_replace('_', '', ucwords($name, '_'));
    }

    protected function getTableName($migrationName)
    {
        // Extract table name from migration name
        // create_users_table -> users
        // add_email_to_users -> users
        // drop_posts_table -> posts
        
        if (preg_match('/create_(.+)_table/', $migrationName, $matches)) {
            return $matches[1];
        } elseif (preg_match('/drop_(.+)_table/', $migrationName, $matches)) {
            return $matches[1];
        } elseif (preg_match('/_to_(.+)/', $migrationName, $matches)) {
            return $matches[1];
        } elseif (preg_match('/_from_(.+)/', $migrationName, $matches)) {
            return $matches[1];
        } elseif (preg_match('/_in_(.+)/', $migrationName, $matches)) {
            return $matches[1];
        }
        
        return 'table_name';
    }

    protected function getStub($className, $tableName, $migrationName)
    {
        // Detect migration type
        if (strpos($migrationName, 'create_') === 0 && strpos($migrationName, '_table') !== false) {
            return $this->getCreateTableStub($className, $tableName);
        } elseif (strpos($migrationName, 'add_') === 0 || strpos($migrationName, '_to_') !== false) {
            return $this->getAddColumnStub($className, $tableName);
        } elseif (strpos($migrationName, 'drop_') === 0) {
            return $this->getDropTableStub($className, $tableName);
        } else {
            return $this->getDefaultStub($className, $tableName);
        }
    }

    protected function getCreateTableStub($className, $tableName)
    {
        $columns = $this->parseColumns();
        $columnDefinitions = $this->generateColumnDefinitions($columns);
        
        return "<?php

use Velocix\Database\Migration;

class {$className} extends Migration
{
    public function up()
    {
        \$this->createTable('{$tableName}', function(\$table) {
            \$table->id();{$columnDefinitions}
            \$table->timestamps();
        });
    }

    public function down()
    {
        \$this->dropTable('{$tableName}');
    }
}";
    }

    protected function getAddColumnStub($className, $tableName)
    {
        return "<?php

use Velocix\Database\Migration;

class {$className} extends Migration
{
    public function up()
    {
        \$this->alterTable('{$tableName}', function(\$table) {
            // Add your columns here
            // \$table->string('column_name');
        });
    }

    public function down()
    {
        \$this->alterTable('{$tableName}', function(\$table) {
            // Drop columns here
            // \$table->dropColumn('column_name');
        });
    }
}";
    }

    protected function getDropTableStub($className, $tableName)
    {
        return "<?php

use Velocix\Database\Migration;

class {$className} extends Migration
{
    public function up()
    {
        \$this->dropTable('{$tableName}');
    }

    public function down()
    {
        \$this->createTable('{$tableName}', function(\$table) {
            \$table->id();
            \$table->timestamps();
        });
    }
}";
    }

    protected function getDefaultStub($className, $tableName)
    {
        return "<?php

use Velocix\Database\Migration;

class {$className} extends Migration
{
    public function up()
    {
        // Write your migration code here
    }

    public function down()
    {
        // Write your rollback code here
    }
}";
    }

    protected function parseColumns()
    {
        $schema = '';
        foreach ($this->args as $arg) {
            if (strpos($arg, '--schema=') === 0) {
                $schema = str_replace('--schema=', '', $arg);
                $schema = trim($schema, '"\'');
                break;
            }
        }

        if (empty($schema)) {
            return [];
        }

        $columns = [];
        $fields = explode(',', $schema);

        foreach ($fields as $field) {
            $parts = explode(':', trim($field));
            $name = $parts[0];
            $type = $parts[1] ?? 'string';
            $modifiers = array_slice($parts, 2);

            $columns[] = [
                'name' => $name,
                'type' => $type,
                'modifiers' => $modifiers
            ];
        }

        return $columns;
    }

    protected function generateColumnDefinitions($columns)
    {
        if (empty($columns)) {
            return '';
        }

        $definitions = '';
        foreach ($columns as $column) {
            $name = $column['name'];
            $type = $column['type'];
            $modifiers = $column['modifiers'];

            $definition = "\n            \$table->{$type}('{$name}')";

            // Add modifiers
            foreach ($modifiers as $modifier) {
                $modifier = trim($modifier);
                
                if ($modifier === 'nullable') {
                    $definition .= "->nullable()";
                } elseif ($modifier === 'unique') {
                    $definition .= "->unique()";
                } elseif ($modifier === 'unsigned') {
                    $definition .= "->unsigned()";
                } elseif ($modifier === 'index') {
                    $definition .= "->index()";
                } elseif (strpos($modifier, 'default=') === 0) {
                    $default = str_replace('default=', '', $modifier);
                    $definition .= "->default({$default})";
                } elseif (strpos($modifier, 'length=') === 0) {
                    // For string length: name:string:length=100
                    $length = str_replace('length=', '', $modifier);
                    $definition = "\n            \$table->{$type}('{$name}', {$length})";
                } elseif (strpos($modifier, 'values=') === 0) {
                    // For enum: status:enum:values=[pending,active,completed]
                    $values = str_replace('values=', '', $modifier);
                    $values = trim($values, '[]');
                    $valueArray = array_map('trim', explode('|', $values));
                    $valueString = "['" . implode("', '", $valueArray) . "']";
                    $definition = "\n            \$table->{$type}('{$name}', {$valueString})";
                }
            }

            $definition .= ';';
            $definitions .= $definition;
        }

        return $definitions;
    }
}