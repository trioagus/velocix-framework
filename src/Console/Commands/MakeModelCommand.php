<?php

namespace Velocix\Console\Commands;

use Velocix\Console\Command;

class MakeModelCommand extends Command
{
    protected $signature = 'make:model';
    protected $description = 'Create a new model class';

    public function handle($args = [])
    {
        if (empty($args[0])) {
            $this->error('Model name is required');
            $this->line('Usage: php velocix make:model User [-m] [--uuid] [--ulid]');
            return;
        }

        $name = $args[0];
        $useUuid = in_array('--uuid', $args);
        $useUlid = in_array('--ulid', $args);
        
        $filename = "app/Models/{$name}.php";

        if (file_exists($filename)) {
            $this->error("Model {$name} already exists!");
            return;
        }

        $stub = $this->getStub($name, $useUuid, $useUlid);
        
        if (!is_dir('app/Models')) {
            mkdir('app/Models', 0755, true);
        }

        file_put_contents($filename, $stub);
        $this->info("Model created successfully: {$filename}");

        // Check if -m flag is present
        if (in_array('-m', $args)) {
            $this->createMigration($name, $useUuid, $useUlid);
        }
    }

    protected function createMigration($modelName, $useUuid = false, $useUlid = false)
    {
        $tableName = $this->getTableName($modelName);
        $migrationName = 'create_' . $tableName . '_table';
        
        $timestamp = date('Y_m_d_His');
        $className = $this->getMigrationClassName($migrationName);
        $filename = "database/migrations/{$timestamp}_{$migrationName}.php";

        if (!is_dir('database/migrations')) {
            mkdir('database/migrations', 0755, true);
        }

        $stub = $this->getMigrationStub($className, $tableName, $useUuid, $useUlid);
        file_put_contents($filename, $stub);
        
        $this->info("Migration created successfully: {$filename}");
    }

    protected function getStub($name, $useUuid = false, $useUlid = false)
    {
        $table = $this->getTableName($name);
        $imports = "use Velocix\\Database\\Model;";
        $useTrait = '';
        
        if ($useUuid) {
            $imports .= "\nuse Velocix\\Database\\Traits\\HasUuids;";
            $useTrait = "\n    use HasUuids;\n";
        } elseif ($useUlid) {
            $imports .= "\nuse Velocix\\Database\\Traits\\HasUlids;";
            $useTrait = "\n    use HasUlids;\n";
        }
        
        return "<?php

namespace App\\Models;

{$imports}

class {$name} extends Model
{{$useTrait}
    protected \$table = '{$table}';
    
    protected \$fillable = [];
    
    protected \$hidden = [];
}";
    }

    protected function getMigrationStub($className, $tableName, $useUuid = false, $useUlid = false)
    {
        $idMethod = 'id()';
        
        if ($useUuid) {
            $idMethod = 'uuid()';
        } elseif ($useUlid) {
            $idMethod = 'ulid()';
        }
        
        return "<?php

use Velocix\\Database\\Migration;

class {$className} extends Migration
{
    public function up()
    {
        \$this->createTable('{$tableName}', function(\$table) {
            \$table->{$idMethod};
            \$table->timestamps();
        });
    }

    public function down()
    {
        \$this->dropTable('{$tableName}');
    }
}";
    }

    protected function getTableName($modelName)
    {
        // Convert model name to snake_case plural
        // User -> users
        // UserProfile -> user_profiles
        // Post -> posts
        
        $name = preg_replace('/(?<!^)[A-Z]/', '_$0', $modelName);
        $name = strtolower($name);
        
        // Simple pluralization
        if (substr($name, -1) === 'y') {
            return substr($name, 0, -1) . 'ies';
        } elseif (in_array(substr($name, -1), ['s', 'x', 'z']) || 
                  in_array(substr($name, -2), ['ch', 'sh'])) {
            return $name . 'es';
        } else {
            return $name . 's';
        }
    }

    protected function getMigrationClassName($migrationName)
    {
        // create_users_table -> CreateUsersTable
        return str_replace('_', '', ucwords($migrationName, '_'));
    }
}