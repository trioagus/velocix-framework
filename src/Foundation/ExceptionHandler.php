<?php

namespace Velocix\Foundation;

use Throwable;

class ExceptionHandler
{
    protected $dontReport = [];

    /**
     * Render exception to response
     */
    public function render(Throwable $e)
    {
        $statusCode = $this->getStatusCode($e);
        $isDebug = $this->isDebugMode();

        // Return JSON for AJAX/API requests
        if ($this->wantsJson()) {
            return $this->renderJson($e, $statusCode);
        }

        // Return HTML error page
        return $this->renderHtml($e, $statusCode, $isDebug);
    }

    /**
     * Check if debug mode is enabled
     */
    protected function isDebugMode()
    {
        $debug = env('APP_DEBUG', false);
        
        // Handle string 'false' or 'true'
        if (is_string($debug)) {
            return strtolower($debug) === 'true';
        }
        
        return (bool) $debug;
    }

    /**
     * Get HTTP status code from exception
     */
    protected function getStatusCode(Throwable $e)
    {
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }

        return 500;
    }

    /**
     * Check if request wants JSON
     */
    protected function wantsJson()
    {
        return (
            isset($_SERVER['HTTP_ACCEPT']) && 
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
        ) || (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest'
        );
    }

    /**
     * Render JSON error response
     */
    protected function renderJson(Throwable $e, $statusCode)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);

        echo json_encode([
            'error' => true,
            'message' => $e->getMessage(),
            'code' => $statusCode,
            'file' => $this->isDebugMode() ? $e->getFile() : null,
            'line' => $this->isDebugMode() ? $e->getLine() : null,
            'trace' => $this->isDebugMode() ? $e->getTraceAsString() : null,
        ], JSON_PRETTY_PRINT);

        exit;
    }

    /**
     * Render HTML error page
     */
    protected function renderHtml(Throwable $e, $statusCode, $isDebug)
    {
        http_response_code($statusCode);

        if ($isDebug) {
            // Debug mode - detailed error
            require_once __DIR__ . '/ErrorPageRenderer.php';
            $renderer = new ErrorPageRenderer();
            echo $renderer->renderDebug($e, $statusCode);
        } else {
            // Production mode - check custom view first
            $customView = $this->getCustomErrorView($statusCode);
            if ($customView && file_exists($customView)) {
                include $customView;
            } else {
                // Use built-in error pages
                require_once __DIR__ . '/ErrorPageRenderer.php';
                require_once __DIR__ . '/ErrorPageTemplates.php';
                $renderer = new ErrorPageRenderer();
                echo $renderer->renderProduction($statusCode);
            }
        }

        exit;
    }

    /**
     * Get custom error view path
     */
    protected function getCustomErrorView($statusCode)
    {
        $viewsPath = dirname(__DIR__, 4) . '/resources/views/errors/';
        return $viewsPath . $statusCode . '.php';
    }
}