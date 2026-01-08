<?php

namespace Velocix\Support;

abstract class ServiceProvider
{
    /**
     * The application instance
     */
    protected $app;

    /**
     * All of the registered bindings
     */
    protected $bindings = [];

    /**
     * All of the registered singletons
     */
    protected $singletons = [];

    /**
     * Create a new service provider instance
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Register any application services
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services
     */
    public function boot()
    {
        //
    }

    /**
     * Register a binding with the container
     */
    protected function bind($abstract, $concrete = null, $shared = false)
    {
        $this->app->bind($abstract, $concrete, $shared);
    }

    /**
     * Register a shared binding in the container
     */
    protected function singleton($abstract, $concrete = null)
    {
        $this->app->singleton($abstract, $concrete);
    }

    /**
     * Get the services provided by the provider
     */
    public function provides()
    {
        return [];
    }

    /**
     * Determine if the provider is deferred
     */
    public function isDeferred()
    {
        return false;
    }
}