<?php

namespace Velocix\View;

class Factory
{
    protected $viewPath;
    protected $cachePath;
    protected $compiler;

    public function __construct($viewPath, $cachePath)
    {
        $this->viewPath = rtrim($viewPath, '/');
        $this->cachePath = rtrim($cachePath, '/');
        $this->compiler = new Compiler();
        
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    public function make($view, $data = [])
    {
        return new View($this, $this->compiler, $view, $data);
    }

    public function exists($view)
    {
        return file_exists($this->getViewPath($view));
    }

    public function getViewPath($view)
    {
        $view = str_replace('.', '/', $view);
        return $this->viewPath . '/' . $view . '.vlx.php';
    }

    public function getCachePath($view)
    {
        return $this->cachePath . '/' . md5($view) . '.php';
    }

    public function getCompiler()
    {
        return $this->compiler;
    }
}