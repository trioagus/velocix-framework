<?php

namespace Velocix\Console\Commands;

use Velocix\Console\Command;

class MakeSeederCommand extends Command
{
    protected $signature = 'make:seeder';
    protected $description = 'Create a new seeder class';

    public function handle($args = [])
    {
        if (empty($args[0])) {
            $this->error('Seeder name is required');
            $this->line('Usage: php velocix make:seeder UserSeeder');
            return;
        }

        $name = $args[0];
        
        // Ensure name ends with 'Seeder'
        if (!str_ends_with($name, 'Seeder')) {
            $name .= 'Seeder';
        }

        $filename = "database/seeders/{$name}.php";

        if (file_exists($filename)) {
            $this->error("Seeder {$name} already exists!");
            return;
        }

        // Create seeders directory if not exists
        if (!is_dir('database/seeders')) {
            mkdir('database/seeders', 0755, true);
        }

        $stub = $this->getStub($name);
        file_put_contents($filename, $stub);
        
        $this->info("✓ Seeder created successfully: {$filename}");
        $this->line('');
        $this->line('Next steps:');
        $this->line("1. Edit {$filename}");
        $this->line("2. Add seeding logic in run() method");
        $this->line("3. Run: php velocix db:seed --class={$name}");
    }

    protected function getStub($name)
    {
        return <<<PHP
<?php

namespace Database\Seeders;

use Velocix\Database\Seeder;

class {$name} extends Seeder
{
    /**
     * Run the database seeds
     */
    public function run()
    {
        // Example: Insert sample data
        /*
        \$this->create('users', [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
        */

        // Or use faker-like helpers:
        /*
        for (\$i = 1; \$i <= 10; \$i++) {
            \$this->insert('posts', [
                'title' => 'Post ' . \$i,
                'content' => \$this->randomText(50),
                'author_id' => \$this->randomNumber(1, 5),
                'created_at' => \$this->randomDate('-6 months', 'now'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        */

        echo "Seeded: {$name}\\n";
    }
}
PHP;
    }
}
