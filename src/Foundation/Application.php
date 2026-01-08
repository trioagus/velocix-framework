<?php

namespace Velocix\Foundation;

use Velocix\Routing\Router;
use Velocix\Container\Container;
use Velocix\Http\Request;
use Velocix\View\Factory;

class Application extends Container
{
    protected static $instance;
    protected $basePath;
    protected $router;
    protected $booted = false;

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
        static::$instance = $this;
        
        $this->registerBaseBindings();
        $this->registerCoreAliases();
    }

    public static function getInstance()
    {
        return static::$instance;
    }

    protected function registerBaseBindings()
    {
        $this->singleton('app', function() {
            return $this;
        });

        $this->singleton('router', function() {
            return new Router($this);
        });

        $this->singleton('request', function() {
            return Request::capture();
        });

        // Register View Factory
        $this->singleton('view', function() {
            $viewPath = $this->resourcePath('views');
            $cachePath = $this->storagePath('framework/views');
            
            return new Factory($viewPath, $cachePath);
        });
    }

    protected function registerCoreAliases()
    {
        $this->alias('app', Application::class);
        $this->alias('router', Router::class);
        $this->alias('request', Request::class);
        $this->alias('view', Factory::class);
    }

    public function basePath($path = '')
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    public function resourcePath($path = '')
    {
        return $this->basePath('resources' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function storagePath($path = '')
    {
        return $this->basePath('storage' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function publicPath($path = '')
    {
        return $this->basePath('public' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function router()
    {
        return $this->make('router');
    }

    public function boot()
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;
    }

    public function run()
    {
        $this->boot();
        
        $request = $this->make('request');
        $router = $this->router();
        
        $response = $router->dispatch($request);
        
        echo $response;
    }
}