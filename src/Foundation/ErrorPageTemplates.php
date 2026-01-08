<?php

namespace Velocix\Foundation;

class ErrorPageTemplates
{
    /**
     * 404 - Not Found
     */
    public function error404()
    {
        return $this->renderTemplate(
            404,
            'Page Not Found',
            'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.',
            '#1f2937',
            '#f9fafb'
        );
    }

    /**
     * 403 - Forbidden
     */
    public function error403()
    {
        return $this->renderTemplate(
            403,
            'Access Denied',
            'You don\'t have permission to access this resource. Please contact the administrator if you believe this is an error.',
            '#dc2626',
            '#fef2f2',
            '#991b1b',
            '#b91c1c'
        );
    }

    /**
     * 401 - Unauthorized
     */
    public function error401()
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 - Unauthorized</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #fefce8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container { text-align: center; max-width: 600px; }
        .error-code {
            font-size: 120px;
            font-weight: 700;
            color: #ca8a04;
            line-height: 1;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 32px;
            font-weight: 600;
            color: #854d0e;
            margin-bottom: 12px;
        }
        .error-message {
            font-size: 16px;
            color: #713f12;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn-home {
            display: inline-block;
            background: #ca8a04;
            color: #ffffff;
            padding: 12px 28px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
            margin-right: 10px;
        }
        .btn-home:hover { background: #a16207; }
        .btn-login {
            display: inline-block;
            background: #ffffff;
            color: #854d0e;
            padding: 12px 28px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            border: 2px solid #ca8a04;
            transition: all 0.2s;
        }
        .btn-login:hover { background: #fefce8; }
        @media (max-width: 768px) {
            .error-code { font-size: 80px; }
            .error-title { font-size: 24px; }
            .btn-home, .btn-login { display: block; margin: 10px auto; width: 200px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">401</div>
        <h1 class="error-title">Authentication Required</h1>
        <p class="error-message">You need to be logged in to access this page. Please authenticate to continue.</p>
        <a href="/login" class="btn-login">Login</a>
        <a href="/" class="btn-home">Go Back Home</a>
    </div>
</body>
</html>
HTML;
    }

    /**
     * 419 - Page Expired
     */
    public function error419()
    {
        return $this->renderTemplate(
            419,
            'Page Expired',
            'Your session has expired. Please refresh the page and try again.',
            '#16a34a',
            '#f0fdf4',
            '#15803d',
            '#15803d',
            'javascript:history.back()',
            'Go Back'
        );
    }

    /**
     * 429 - Too Many Requests
     */
    public function error429()
    {
        return $this->renderTemplate(
            429,
            'Too Many Requests',
            'You have made too many requests. Please slow down and try again later.',
            '#f59e0b',
            '#fef3c7',
            '#d97706',
            '#d97706'
        );
    }

    /**
     * 500 - Internal Server Error
     */
    public function error500()
    {
        return $this->renderTemplate(
            500,
            'Server Error',
            'Something went wrong on our end. We\'re working to fix the issue. Please try again later.',
            '#ef4444',
            '#fafafa',
            '#1f2937',
            '#374151'
        );
    }

    /**
     * 503 - Service Unavailable
     */
    public function error503()
    {
        return $this->renderTemplate(
            503,
            'Service Unavailable',
            'We\'re currently performing maintenance. The site will be back online shortly. Thank you for your patience.',
            '#3b82f6',
            '#eff6ff',
            '#1e3a8a',
            '#2563eb',
            'javascript:location.reload()',
            'Retry'
        );
    }

    /**
     * Generic error page
     */
    public function errorGeneric($statusCode)
    {
        return $this->renderTemplate(
            $statusCode,
            "Error {$statusCode}",
            'An error occurred while processing your request. Please try again later.',
            '#1f2937',
            '#f9fafb'
        );
    }

    /**
     * Render error template
     */
    protected function renderTemplate(
        $code, 
        $title, 
        $message, 
        $primaryColor, 
        $bgColor, 
        $titleColor = null,
        $btnHoverColor = null,
        $btnLink = '/',
        $btnText = 'Go Back Home'
    ) {
        $titleColor = $titleColor ?? $primaryColor;
        $btnHoverColor = $btnHoverColor ?? '#374151';
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$code} - {$title}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: {$bgColor};
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container { text-align: center; max-width: 600px; }
        .error-code {
            font-size: 120px;
            font-weight: 700;
            color: {$primaryColor};
            line-height: 1;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 32px;
            font-weight: 600;
            color: {$titleColor};
            margin-bottom: 12px;
        }
        .error-message {
            font-size: 16px;
            color: {$titleColor};
            margin-bottom: 30px;
            line-height: 1.6;
            opacity: 0.8;
        }
        .btn-home {
            display: inline-block;
            background: {$primaryColor};
            color: #ffffff;
            padding: 12px 28px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }
        .btn-home:hover { background: {$btnHoverColor}; }
        @media (max-width: 768px) {
            .error-code { font-size: 80px; }
            .error-title { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">{$code}</div>
        <h1 class="error-title">{$title}</h1>
        <p class="error-message">{$message}</p>
        <a href="{$btnLink}" class="btn-home">{$btnText}</a>
    </div>
</body>
</html>
HTML;
    }
}