<?php

namespace Velocix\Routing;

use Velocix\Container\Container;
use Velocix\Http\Request;
use Velocix\Http\Pipeline;

class Route
{
    protected $method;
    protected $uri;
    protected $action;
    protected $middleware = [];
    protected $parameters = [];
    protected $name;

    public function __construct($method, $uri, $action)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->action = $action;
    }

    public function middleware($middleware)
    {
        $this->middleware = array_merge(
            $this->middleware, 
            is_array($middleware) ? $middleware : [$middleware]
        );
        
        return $this;
    }

    public function name($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function matches(Request $request)
    {
        if ($request->method() !== $this->method) {
            return false;
        }

        $pattern = $this->convertToRegex($this->uri);
        
        if (preg_match($pattern, $request->path(), $matches)) {
            array_shift($matches);
            $this->parameters = $matches;
            return true;
        }

        return false;
    }

    /**
     * Check if URI matches regardless of HTTP method
     */
    public function uriMatches(Request $request)
    {
        $pattern = $this->convertToRegex($this->uri);
        
        if (preg_match($pattern, $request->path(), $matches)) {
            return true;
        }

        return false;
    }

    /**
     * Get HTTP method for this route
     */
    public function getMethod()
    {
        return $this->method;
    }

    protected function convertToRegex($uri)
    {
        $uri = preg_replace('/\{([a-zA-Z]+)\}/', '([^/]+)', $uri);
        return '#^' . $uri . '$#';
    }

    public function run(Request $request, Container $container)
    {
        $pipeline = new Pipeline($container);
        
        return $pipeline
            ->send($request)
            ->through($this->middleware)
            ->then(function($request) use ($container) {
                return $this->runController($request, $container);
            });
    }

    protected function runController(Request $request, Container $container)
    {
        if (is_callable($this->action)) {
            return call_user_func_array(
                $this->action, 
                array_merge([$request], $this->parameters)
            );
        }

        if (is_string($this->action)) {
            list($controller, $method) = explode('@', $this->action);
            
            $controllerClass = $this->resolveControllerClass($controller);
            $instance = $container->make($controllerClass);
            
            return call_user_func_array(
                [$instance, $method], 
                array_merge([$request], $this->parameters)
            );
        }

        throw new \Exception("Invalid route action");
    }

    protected function resolveControllerClass($controller)
    {
        if (strpos($controller, '\\') !== false) {
            return $controller;
        }

        $namespaces = [
            'App\\Http\\Controllers\\',
            'App\\Controllers\\',
        ];

        foreach ($namespaces as $namespace) {
            $fullClass = $namespace . $controller;
            if (class_exists($fullClass)) {
                return $fullClass;
            }
        }

        throw new \Exception("Controller not found: {$controller}");
    }
}