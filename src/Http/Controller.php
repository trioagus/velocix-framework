<?php

namespace Velocix\Http;

use Velocix\Auth\Auth;

abstract class Controller
{
    protected function json($data, $status = 200)
    {
        return Response::json($data, $status);
    }

    protected function view($view, $data = [])
    {
        // Auto inject authenticated user ke semua view (pakai Auth class)
        if (!isset($data['user'])) {
            $data['user'] = Auth::user();
        }
        
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