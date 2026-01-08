<?php

namespace Velocix\Http;

use Velocix\Container\Container;

class Pipeline
{
    protected $container;
    protected $passable;
    protected $pipes = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function send($passable)
    {
        $this->passable = $passable;
        return $this;
    }

    public function through($pipes)
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();
        return $this;
    }

    public function then(\Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $destination
        );

        return $pipeline($this->passable);
    }

    protected function carry()
    {
        return function($stack, $pipe) {
            return function($passable) use ($stack, $pipe) {
                $middleware = is_string($pipe) 
                    ? $this->container->make($pipe) 
                    : $pipe;

                return $middleware->handle($passable, $stack);
            };
        };
    }
}