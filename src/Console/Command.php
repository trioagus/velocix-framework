<?php

namespace Velocix\Console;

abstract class Command
{
    protected $signature;
    protected $description;

    abstract public function handle($args = []);

    public function getSignature()
    {
        return $this->signature;
    }

    public function getDescription()
    {
        return $this->description;
    }

    protected function info($message)
    {
        echo "\033[32m{$message}\033[0m\n";
    }

    protected function error($message)
    {
        echo "\033[31m{$message}\033[0m\n";
    }

    protected function warn($message)
    {
        echo "\033[33m{$message}\033[0m\n";
    }

    protected function line($message)
    {
        echo "{$message}\n";
    }

    protected function ask($question)
    {
        echo "{$question}: ";
        return trim(fgets(STDIN));
    }

    protected function confirm($question)
    {
        echo "{$question} (yes/no): ";
        $answer = trim(fgets(STDIN));
        return in_array(strtolower($answer), ['yes', 'y']);
    }
}
