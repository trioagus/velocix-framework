<?php

namespace Velocix\Routing;

use Velocix\Container\Container;
use Velocix\Http\Request;
use Velocix\Http\Response;
use Velocix\Foundation\Exceptions\NotFoundException;
use Velocix\Foundation\Exceptions\MethodNotAllowedException;

class Router
{
    protected $container;
    protected $routes = [];
    protected $groupStack = [];
    protected $middleware = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get($uri, $action)
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post($uri, $action)
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put($uri, $action)
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function delete($uri, $action)
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    public function patch($uri, $action)
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    public function group($attributes, $callback)
    {
        if (is_string($attributes)) {
            $attributes = ['prefix' => $attributes];
        }

        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    protected function addRoute($method, $uri, $action)
    {
        $uri = $this->prefix($uri);
        
        $route = new Route($method, $uri, $action);
        
        // Apply group middleware
        if (!empty($this->groupStack)) {
            foreach ($this->groupStack as $group) {
                if (isset($group['middleware'])) {
                    $route->middleware($group['middleware']);
                }
            }
        }
        
        $this->routes[] = $route;
        
        return $route;
    }

    protected function prefix($uri)
    {
        $prefix = '';
        
        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }
        
        return '/' . trim($prefix . '/' . trim($uri, '/'), '/');
    }

    public function dispatch(Request $request)
    {
        $matchedRoute = null;
        $allowedMethods = [];
        
        foreach ($this->routes as $route) {
            if ($route->matches($request)) {
                return $route->run($request, $this->container);
            }
            
            // Check if URI matches but method doesn't
            if ($route->uriMatches($request)) {
                $allowedMethods[] = $route->getMethod();
            }
        }
        
        // If URI matches but method is wrong, throw 405
        if (!empty($allowedMethods)) {
            throw new MethodNotAllowedException(
                "Method {$request->method()} not allowed for this route.",
                $allowedMethods
            );
        }
        
        // No route found at all, throw 404
        throw new NotFoundException(
            "Route not found: {$request->method()} {$request->path()}"
        );
    }
}