<?php

namespace Velocix\Console\Commands;

use Velocix\Console\Command;

class MakeServiceCommand extends Command
{
    protected $signature = 'make:service';
    protected $description = 'Create a new service class with optional repository';

    public function handle($args = [])
    {
        if (empty($args[0])) {
            $this->error('Service name is required');
            $this->line('Usage: php velocix make:service UserService [--model=User] [--repository]');
            return;
        }

        $name = $args[0];
        $model = $this->getOption('model', $args);
        $withRepository = $this->hasOption('repository', $args);

        // Create Service
        $this->createService($name, $model, $withRepository);

        // Create Repository if requested
        if ($withRepository && $model) {
            $this->createRepository($model);
        }

        $this->info("✓ Service created successfully!");
        
        if ($withRepository && $model) {
            $this->info("✓ Repository created successfully!");
            $this->line('');
            $this->line('Next steps:');
            $this->line("1. Register repository in app/Providers/AppServiceProvider.php");
            $this->line("2. Bind interface: \$this->app->bind({$model}RepositoryInterface::class, {$model}Repository::class);");
        }
    }

    protected function createService($name, $model = null, $withRepository = false)
    {
        $filename = "app/Services/{$name}.php";

        if (file_exists($filename)) {
            $this->error("Service {$name} already exists!");
            return;
        }

        $stub = $withRepository && $model 
            ? $this->getServiceWithRepositoryStub($name, $model)
            : $this->getBasicServiceStub($name, $model);
        
        if (!is_dir('app/Services')) {
            mkdir('app/Services', 0755, true);
        }

        file_put_contents($filename, $stub);
    }

    protected function createRepository($model)
    {
        // Create Repository Interface
        $interfaceFile = "app/Repositories/Contracts/{$model}RepositoryInterface.php";
        if (!file_exists($interfaceFile)) {
            if (!is_dir('app/Repositories/Contracts')) {
                mkdir('app/Repositories/Contracts', 0755, true);
            }
            file_put_contents($interfaceFile, $this->getRepositoryInterfaceStub($model));
        }

        // Create Repository Implementation
        $repoFile = "app/Repositories/{$model}Repository.php";
        if (!file_exists($repoFile)) {
            if (!is_dir('app/Repositories')) {
                mkdir('app/Repositories', 0755, true);
            }
            file_put_contents($repoFile, $this->getRepositoryStub($model));
        }
    }

    protected function getBasicServiceStub($name, $model = null)
    {
        $modelUse = $model ? "use App\\Models\\{$model};\n" : '';
        $modelProperty = $model ? "\n    protected \$model;\n" : '';
        $modelConstruct = $model ? "\n        \$this->model = new {$model}();" : '';

        return <<<PHP
<?php

namespace App\Services;

{$modelUse}
class {$name}
{{$modelProperty}
    public function __construct()
    {{$modelConstruct}
    }

    /**
     * Get all records with pagination
     */
    public function getAll(int \$perPage = 15)
    {
        // Add your business logic here
        return [];
    }

    /**
     * Get record by ID
     */
    public function getById(\$id)
    {
        // Add your business logic here
        return null;
    }

    /**
     * Create new record
     */
    public function create(array \$data)
    {
        // Validate and sanitize data
        \$validated = \$this->validate(\$data);
        
        // Add your business logic here
        return \$validated;
    }

    /**
     * Update existing record
     */
    public function update(\$id, array \$data)
    {
        // Validate and sanitize data
        \$validated = \$this->validate(\$data);
        
        // Add your business logic here
        return \$validated;
    }

    /**
     * Delete record
     */
    public function delete(\$id)
    {
        // Add your business logic here
        return true;
    }

    /**
     * Validate and sanitize input data
     */
    protected function validate(array \$data)
    {
        // Add validation logic here
        // XSS Protection: htmlspecialchars() for user input
        // CSRF: Verify token in controller before calling service
        
        return array_map(function(\$value) {
            return is_string(\$value) ? htmlspecialchars(\$value, ENT_QUOTES, 'UTF-8') : \$value;
        }, \$data);
    }
}
PHP;
    }

    protected function getServiceWithRepositoryStub($name, $model)
    {
        return <<<PHP
<?php

namespace App\Services;

use App\Repositories\Contracts\\{$model}RepositoryInterface;

class {$name}
{
    protected \$repository;

    public function __construct({$model}RepositoryInterface \$repository)
    {
        \$this->repository = \$repository;
    }

    /**
     * Get all records with pagination
     */
    public function getAll(int \$perPage = 15, array \$filters = [])
    {
        return \$this->repository->paginate(\$perPage, \$filters);
    }

    /**
     * Get record by ID
     */
    public function getById(\$id)
    {
        \$record = \$this->repository->find(\$id);
        
        if (!\$record) {
            throw new \Exception('{$model} not found');
        }
        
        return \$record;
    }

    /**
     * Create new record
     */
    public function create(array \$data)
    {
        // Validate and sanitize data
        \$validated = \$this->validateAndSanitize(\$data);
        
        // Business logic
        return \$this->repository->create(\$validated);
    }

    /**
     * Update existing record
     */
    public function update(\$id, array \$data)
    {
        \$record = \$this->getById(\$id);
        
        // Validate and sanitize data
        \$validated = \$this->validateAndSanitize(\$data);
        
        // Business logic
        return \$this->repository->update(\$id, \$validated);
    }

    /**
     * Delete record
     */
    public function delete(\$id)
    {
        \$record = \$this->getById(\$id);
        
        return \$this->repository->delete(\$id);
    }

    /**
     * Validate and sanitize input data (XSS Protection)
     */
    protected function validateAndSanitize(array \$data)
    {
        // Add your validation rules here
        
        // XSS Protection: Sanitize string inputs
        return array_map(function(\$value) {
            if (is_string(\$value)) {
                return htmlspecialchars(\$value, ENT_QUOTES, 'UTF-8');
            }
            return \$value;
        }, \$data);
    }

    /**
     * Search records
     */
    public function search(string \$query, int \$perPage = 15)
    {
        return \$this->repository->search(\$query, \$perPage);
    }
}
PHP;
    }

    protected function getRepositoryInterfaceStub($model)
    {
        return <<<PHP
<?php

namespace App\Repositories\Contracts;

interface {$model}RepositoryInterface
{
    public function all();
    public function find(\$id);
    public function create(array \$data);
    public function update(\$id, array \$data);
    public function delete(\$id);
    public function paginate(int \$perPage = 15, array \$filters = []);
    public function search(string \$query, int \$perPage = 15);
}
PHP;
    }

    protected function getRepositoryStub($model)
    {
        $tableName = strtolower($model) . 's'; // Simple pluralization

        return <<<PHP
<?php

namespace App\Repositories;

use App\Models\\{$model};
use App\Repositories\Contracts\\{$model}RepositoryInterface;

class {$model}Repository implements {$model}RepositoryInterface
{
    protected \$model;

    public function __construct({$model} \$model)
    {
        \$this->model = \$model;
    }

    public function all()
    {
        return \$this->model->all();
    }

    public function find(\$id)
    {
        return \$this->model->find(\$id);
    }

    public function create(array \$data)
    {
        return \$this->model->create(\$data);
    }

    public function update(\$id, array \$data)
    {
        \$record = \$this->find(\$id);
        
        if (!\$record) {
            throw new \Exception('{$model} not found');
        }
        
        return \$record->update(\$data);
    }

    public function delete(\$id)
    {
        \$record = \$this->find(\$id);
        
        if (!\$record) {
            throw new \Exception('{$model} not found');
        }
        
        return \$record->delete();
    }

    public function paginate(int \$perPage = 15, array \$filters = [])
    {
        \$query = \$this->model->query();
        
        // Apply filters
        foreach (\$filters as \$key => \$value) {
            if (!\$value) continue;
            
            \$query->where(\$key, 'LIKE', "%{\$value}%");
        }
        
        return \$query->paginate(\$perPage);
    }

    public function search(string \$query, int \$perPage = 15)
    {
        // Implement search logic based on your model
        // Example: search in name and email fields
        return \$this->model
            ->where('name', 'LIKE', "%{\$query}%")
            ->orWhere('email', 'LIKE', "%{\$query}%")
            ->paginate(\$perPage);
    }

    /**
     * Get with relationships
     */
    public function findWithRelations(\$id, array \$relations = [])
    {
        \$query = \$this->model->query();
        
        foreach (\$relations as \$relation) {
            \$query->with(\$relation);
        }
        
        return \$query->find(\$id);
    }
}
PHP;
    }

    protected function getOption($name, $args)
    {
        foreach ($args as $arg) {
            if (strpos($arg, "--{$name}=") === 0) {
                return substr($arg, strlen("--{$name}="));
            }
        }
        return null;
    }

    protected function hasOption($name, $args)
    {
        return in_array("--{$name}", $args);
    }
}