<?php

namespace Velocix\Http;

class JsonResponse
{
    /**
     * Send clean JSON response (no HTML/errors mixed)
     * 
     * @param array $data
     * @param int $statusCode
     * @return void
     */
    public static function send($data, $statusCode = 200)
    {
        // Clear ALL output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        http_response_code($statusCode);
        
        // Send JSON
        echo json_encode($data);
        exit;
    }
    
    /**
     * Send success response
     */
    public static function success($message, $data = null, $statusCode = 200)
    {
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        static::send($response, $statusCode);
    }
    
    /**
     * Send error response
     */
    public static function error($message, $errors = null, $statusCode = 400)
    {
        $response = [
            'success' => false,
            'error' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        static::send($response, $statusCode);
    }
    
    /**
     * Send validation error
     */
    public static function validationError($errors, $message = 'Validation failed')
    {
        static::error($message, $errors, 422);
    }
    
    /**
     * Send redirect response
     */
    public static function redirect($url, $message = null)
    {
        $response = [
            'success' => true,
            'redirect' => $url
        ];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        static::send($response);
    }
}