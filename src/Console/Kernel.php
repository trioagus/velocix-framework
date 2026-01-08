<?php

namespace Velocix\Console;

use Velocix\Foundation\Application;

class Kernel
{
    protected $commands = [];
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->registerCommands();
    }

    protected function registerCommands()
    {
        $this->commands = [
            new Commands\MakeControllerCommand(),
            new Commands\MakeModelCommand(),
            new Commands\MakeServiceCommand(),
            new Commands\MakeMigrationCommand(),
            new Commands\MakeMiddlewareCommand(),
            new Commands\MakeRequestCommand(),
            new Commands\MakeAuthCommand(),
            new Commands\MigrateCommand(),
            new Commands\MigrateRollbackCommand(),
            new Commands\ServeCommand(),
            new Commands\KeyGenerateCommand(),
            new Commands\CacheClearCommand(),
            new Commands\StorageLinkCommand(),
            new Commands\LogClearCommand(),
            new Commands\OptimizeCommand(),
            new Commands\MakeSeederCommand(),
            new Commands\DbSeedCommand(),
            new Commands\MigrateFreshCommand(),
        ];
    }

    public function handle($argv)
    {
        if (count($argv) < 2) {
            $this->showHelp();
            return;
        }

        $commandName = $argv[1];
        $arguments = array_slice($argv, 2);

        foreach ($this->commands as $command) {
            if ($command->getSignature() === $commandName) {
                $command->handle($arguments);
                return;
            }
        }

        $this->error("Command '{$commandName}' not found.");
        $this->showHelp();
    }

    protected function showHelp()
    {
        echo "\n\033[33m⚡ Velocix Framework\033[0m\n\n";
        echo "Usage:\n  php velocix <command> [arguments]\n\n";
        echo "Available commands:\n";
        
        foreach ($this->commands as $command) {
            printf("  \033[32m%-30s\033[0m %s\n", 
                $command->getSignature(), 
                $command->getDescription()
            );
        }
        
        echo "\n";
    }

    protected function error($message)
    {
        echo "\033[31m{$message}\033[0m\n";
    }
}