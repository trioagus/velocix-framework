<?php

namespace Velocix\View;

class View
{
    protected $factory;
    protected $compiler;
    protected $view;
    protected $data;
    public $path;

    public function __construct(Factory $factory, Compiler $compiler, $view, $data = [])
    {
        $this->factory = $factory;
        $this->compiler = $compiler;
        $this->view = $view;
        $this->data = $data;
        $this->path = $factory->getViewPath($view);
    }

    public function render()
    {
        if (!file_exists($this->path)) {
            throw new \Exception("View [{$this->view}] not found at [{$this->path}]");
        }

        $cachePath = $this->factory->getCachePath($this->view);
        
        // ALWAYS compile (for debugging)
        $sourceContent = file_get_contents($this->path);
        $compiled = $this->compiler->compile($sourceContent);
        
        // Ensure cache directory exists
        $cacheDir = dirname($cachePath);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        file_put_contents($cachePath, $compiled);
        
        // Debug: uncomment to see compiled output
        // echo "<pre>" . htmlspecialchars($compiled) . "</pre>"; die();

        return $this->evaluate($cachePath, $this->data);
    }

    protected function evaluate($__path, $__data)
    {
        extract($__data);
        
        ob_start();
        include $__path;
        return ob_get_clean();
    }

    public function __toString()
    {
        return $this->render();
    }
}