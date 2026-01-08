<?php

namespace Velocix\Http;

class Response
{
    protected $content;
    protected $statusCode;
    protected $headers = [];

    public function __construct($content = '', $statusCode = 200, $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public static function make($content = '', $statusCode = 200, $headers = [])
    {
        return new static($content, $statusCode, $headers);
    }

    public static function json($data, $statusCode = 200, $headers = [])
    {
        $headers['Content-Type'] = 'application/json';
        return new static(json_encode($data), $statusCode, $headers);
    }

    public static function view($view, $data = [], $statusCode = 200)
    {
        $content = view($view, $data)->render();
        return new static($content, $statusCode);
    }

    public function send()
    {
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }
        
        echo $this->content;
    }

    public function __toString()
    {
        return (string) $this->content;
    }
}