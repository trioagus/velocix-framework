<?php

namespace Velocix\Console\Commands;

use Velocix\Console\Command;

class MakeControllerCommand extends Command
{
    protected $signature = 'make:controller';
    protected $description = 'Create a new controller class';

    public function handle($args = [])
    {
        if (empty($args[0])) {
            $this->error('Controller name is required');
            $this->line('Usage: php velocix make:controller UserController');
            return;
        }

        $name = $args[0];
        
        // Handle namespaced controllers (e.g., Api/UserController)
        $path = 'app/Http/Controllers/';
        if (strpos($name, '/') !== false) {
            $parts = explode('/', $name);
            $name = array_pop($parts);
            $path .= implode('/', $parts) . '/';
        }

        $filename = $path . $name . '.php';

        if (file_exists($filename)) {
            $this->error("Controller {$name} already exists!");
            return;
        }

        $namespace = 'App\\Http\\Controllers';
        if (strpos($filename, '/') !== false) {
            $subPath = dirname(str_replace('app/Http/Controllers/', '', $filename));
            if ($subPath !== '.') {
                $namespace .= '\\' . str_replace('/', '\\', $subPath);
            }
        }

        $stub = $this->getStub($name, $namespace);
        
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        file_put_contents($filename, $stub);
        $this->info("Controller created successfully: {$filename}");
    }

    protected function getStub($name, $namespace)
    {
        return <<<PHP
<?php

namespace {$namespace};

use Velocix\Http\Controller;
use Velocix\Http\Request;

class {$name} extends Controller
{
    public function index(Request \$request)
    {
        // For SPA requests
        if (\$request->ajax() || \$request->wantsJson()) {
            return \$this->json([
                'html' => view('welcome', ['title' => 'Welcome'])->render(),
                'title' => 'Welcome'
            ]);
        }

        // For full page load (SSR)
        return \$this->view('welcome', [
            'title' => 'Welcome to Velocix'
        ]);
    }

    public function show(Request \$request, \$id)
    {
        return \$this->json([
            'id' => \$id,
            'message' => 'Show method'
        ]);
    }

    public function store(Request \$request)
    {
        \$data = \$request->all();
        
        return \$this->json([
            'message' => 'Created successfully',
            'data' => \$data
        ], 201);
    }

    public function update(Request \$request, \$id)
    {
        \$data = \$request->all();
        
        return \$this->json([
            'message' => 'Updated successfully',
            'id' => \$id,
            'data' => \$data
        ]);
    }

    public function destroy(Request \$request, \$id)
    {
        return \$this->json([
            'message' => 'Deleted successfully',
            'id' => \$id
        ]);
    }
}
PHP;
    }
}