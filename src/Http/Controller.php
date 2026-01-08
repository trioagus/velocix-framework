<?php

namespace Velocix\Http;

abstract class Controller
{
    protected function json($data, $status = 200)
    {
        return Response::json($data, $status);
    }

    protected function view($view, $data = [])
    {
        return Response::view($view, $data);
    }

    protected function redirect($url)
    {
        header("Location: {$url}");
        exit;
    }

    protected function back()
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return $this->redirect($referer);
    }
}