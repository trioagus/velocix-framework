<?php

namespace Velocix\Console\Commands;

use Velocix\Console\Command;

class MakeMiddlewareCommand extends Command
{
    protected $signature = 'make:middleware';
    protected $description = 'Create a new middleware class';

    public function handle($args = [])
    {
        if (empty($args[0])) {
            $this->error('Middleware name is required');
            $this->line('Usage: php velocix make:middleware CheckRole');
            return;
        }

        $name = $args[0];
        $filename = "app/Http/Middleware/{$name}.php";

        if (file_exists($filename)) {
            $this->error("Middleware {$name} already exists!");
            return;
        }

        $stub = $this->getStub($name);
        
        if (!is_dir('app/Http/Middleware')) {
            mkdir('app/Http/Middleware', 0755, true);
        }

        file_put_contents($filename, $stub);
        $this->info("Middleware created successfully: {$filename}");
    }

    protected function getStub($name)
    {
        return <<<PHP
<?php

namespace App\Http\Middleware;

use Velocix\Http\Middleware;
use Velocix\Http\Request;

class {$name} extends Middleware
{
    public function handle(Request \$request, \$next)
    {
        // Middleware logic here
        
        return \$next(\$request);
    }
}
PHP;
    }
}