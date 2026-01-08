<?php

namespace Velocix\Http;

class Request
{
    protected $query;
    protected $request;
    protected $server;
    protected $files;
    protected $cookies;
    protected $headers;

    public static function capture()
    {
        $request = new static();
        $request->query = $_GET;
        $request->request = $_POST;
        $request->server = $_SERVER;
        $request->files = $_FILES;
        $request->cookies = $_COOKIE;
        $request->headers = getallheaders() ?: [];
        
        return $request;
    }

    public function method()
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function path()
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        return parse_url($uri, PHP_URL_PATH);
    }

    public function url()
    {
        $protocol = (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') 
            ? 'https' : 'http';
        $host = $this->server['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host . $this->path();
    }

    public function input($key = null, $default = null)
    {
        $input = array_merge($this->query, $this->request, $this->json());
        
        if (is_null($key)) {
            return $input;
        }
        
        return $input[$key] ?? $default;
    }

    public function all()
    {
        return $this->input();
    }

    public function only($keys)
    {
        $results = [];
        $input = $this->all();
        
        foreach ((array) $keys as $key) {
            if (isset($input[$key])) {
                $results[$key] = $input[$key];
            }
        }
        
        return $results;
    }

    public function except($keys)
    {
        $input = $this->all();
        
        foreach ((array) $keys as $key) {
            unset($input[$key]);
        }
        
        return $input;
    }

    public function json()
    {
        $content = file_get_contents('php://input');
        return json_decode($content, true) ?? [];
    }

    public function isJson()
    {
        return strpos($this->header('Content-Type', ''), 'application/json') !== false;
    }

    public function expectsJson()
    {
        return $this->isJson() || $this->wantsJson();
    }

    public function wantsJson()
    {
        $acceptable = $this->header('Accept', '');
        return strpos($acceptable, 'application/json') !== false;
    }

    public function ajax()
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    public function header($key, $default = null)
    {
        $key = str_replace('_', '-', strtolower($key));
        
        foreach ($this->headers as $header => $value) {
            if (strtolower($header) === $key) {
                return $value;
            }
        }
        
        return $default;
    }

    public function bearerToken()
    {
        $header = $this->header('Authorization', '');
        
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}
