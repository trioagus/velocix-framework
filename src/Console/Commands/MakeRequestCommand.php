<?php

namespace Velocix\Console\Commands;

use Velocix\Console\Command;

class MakeRequestCommand extends Command
{
    protected $signature = 'make:request';
    protected $description = 'Create a new form request class';

    public function handle($args = [])
    {
        if (empty($args[0])) {
            $this->error('Request name is required');
            $this->line('Usage: php velocix make:request CreateUserRequest');
            return;
        }

        $name = $args[0];
        $filename = "app/Http/Requests/{$name}.php";

        if (file_exists($filename)) {
            $this->error("Request {$name} already exists!");
            return;
        }

        $stub = $this->getStub($name);
        
        if (!is_dir('app/Http/Requests')) {
            mkdir('app/Http/Requests', 0755, true);
        }

        file_put_contents($filename, $stub);
        $this->info("Request created successfully: {$filename}");
    }

    protected function getStub($name)
    {
        return <<<PHP
<?php

namespace App\Http\Requests;

use Velocix\Validation\FormRequest;

class {$name} extends FormRequest
{
    public function rules()
    {
        return [
            // 'field' => 'required|email',
        ];
    }

    public function messages()
    {
        return [
            // 'field.required' => 'Custom error message',
        ];
    }

    public function authorize()
    {
        return true;
    }
}
PHP;
    }
}