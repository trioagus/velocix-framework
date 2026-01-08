<?php

namespace Velocix\Foundation;

use Throwable;

class ErrorPageRenderer
{
    /**
     * Render debug error page
     */
    public function renderDebug(Throwable $e, $statusCode)
    {
        $message = htmlspecialchars($e->getMessage());
        $file = htmlspecialchars($e->getFile());
        $line = $e->getLine();
        $trace = htmlspecialchars($e->getTraceAsString());
        $class = get_class($e);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error {$statusCode}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            color: #1a1a1a;
            line-height: 1.6;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            border-bottom: 3px solid #dc2626;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .error-code {
            font-size: 18px;
            font-weight: 700;
            color: #dc2626;
            margin-bottom: 8px;
        }
        .error-class {
            font-size: 14px;
            color: #6b7280;
            font-family: 'Courier New', monospace;
        }
        .section {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #374151;
            margin-bottom: 12px;
        }
        .error-text {
            color: #dc2626;
            font-size: 15px;
            line-height: 1.8;
        }
        .file-path {
            font-family: 'Courier New', monospace;
            background: #ffffff;
            border: 1px solid #d1d5db;
            padding: 10px;
            border-radius: 4px;
            font-size: 13px;
            color: #1f2937;
            word-break: break-all;
            margin-bottom: 10px;
        }
        .line-info {
            font-size: 13px;
            color: #6b7280;
        }
        .line-number {
            font-weight: 700;
            color: #1f2937;
        }
        .trace-container {
            background: #1f2937;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .trace-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .trace-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #f3f4f6;
        }
        .copy-btn {
            background: #374151;
            color: #f9fafb;
            border: 1px solid #4b5563;
            padding: 6px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.2s;
        }
        .copy-btn:hover {
            background: #4b5563;
        }
        .trace-content {
            color: #d1d5db;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.8;
            overflow-x: auto;
            white-space: pre;
        }
        .footer {
            text-align: center;
            color: #9ca3af;
            font-size: 13px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
        }
        @media (max-width: 768px) {
            body { padding: 15px; }
            .section { padding: 15px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="error-code">ERROR {$statusCode}</div>
            <div class="error-class">{$class}</div>
        </div>

        <div class="section">
            <div class="section-title">Error Message</div>
            <div class="error-text">{$message}</div>
        </div>

        <div class="section">
            <div class="section-title">Location</div>
            <div class="file-path">{$file}</div>
            <div class="line-info">at line <span class="line-number">{$line}</span></div>
        </div>

        <div class="trace-container">
            <div class="trace-header">
                <div class="trace-title">Stack Trace</div>
                <button class="copy-btn" onclick="copyTrace()">Copy</button>
            </div>
            <div class="trace-content" id="trace-content">{$trace}</div>
        </div>

        <div class="footer">
            Velocix Framework - Debug Mode
        </div>
    </div>

    <script>
        function copyTrace() {
            const trace = document.getElementById('trace-content').textContent;
            navigator.clipboard.writeText(trace).then(() => {
                const btn = document.querySelector('.copy-btn');
                const original = btn.textContent;
                btn.textContent = 'Copied';
                setTimeout(() => btn.textContent = original, 2000);
            });
        }
    </script>
</body>
</html>
HTML;
    }

    /**
     * Render production error page
     */
    public function renderProduction($statusCode)
    {
        // Load specific error page template
        $templates = new ErrorPageTemplates();
        
        switch ($statusCode) {
            case 404:
                return $templates->error404();
            case 403:
                return $templates->error403();
            case 401:
                return $templates->error401();
            case 419:
                return $templates->error419();
            case 429:
                return $templates->error429();
            case 500:
                return $templates->error500();
            case 503:
                return $templates->error503();
            default:
                return $templates->errorGeneric($statusCode);
        }
    }
}