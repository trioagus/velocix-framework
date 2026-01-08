<?php

namespace Velocix\Foundation\Exceptions;

use Exception;

/**
 * Base HTTP Exception
 */
abstract class HttpException extends Exception
{
    protected $statusCode;
    protected $headers = [];

    public function __construct($message = "", $statusCode = 500, array $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        
        parent::__construct($message);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getHeaders()
    {
        return $this->headers;
    }
}

/**
 * 400 Bad Request
 */
class BadRequestException extends HttpException
{
    public function __construct($message = "Bad Request")
    {
        parent::__construct($message, 400);
    }
}

/**
 * 401 Unauthorized
 */
class UnauthorizedException extends HttpException
{
    public function __construct($message = "Unauthorized")
    {
        parent::__construct($message, 401);
    }
}

/**
 * 403 Forbidden
 */
class ForbiddenException extends HttpException
{
    public function __construct($message = "Forbidden")
    {
        parent::__construct($message, 403);
    }
}

/**
 * 404 Not Found
 */
class NotFoundException extends HttpException
{
    public function __construct($message = "Not Found")
    {
        parent::__construct($message, 404);
    }
}

/**
 * 405 Method Not Allowed
 */
class MethodNotAllowedException extends HttpException
{
    public function __construct($message = "Method Not Allowed", array $allowedMethods = [])
    {
        $headers = [];
        if (!empty($allowedMethods)) {
            $headers['Allow'] = implode(', ', $allowedMethods);
        }
        
        parent::__construct($message, 405, $headers);
    }
}

/**
 * 408 Request Timeout
 */
class RequestTimeoutException extends HttpException
{
    public function __construct($message = "Request Timeout")
    {
        parent::__construct($message, 408);
    }
}

/**
 * 409 Conflict
 */
class ConflictException extends HttpException
{
    public function __construct($message = "Conflict")
    {
        parent::__construct($message, 409);
    }
}

/**
 * 410 Gone
 */
class GoneException extends HttpException
{
    public function __construct($message = "Gone")
    {
        parent::__construct($message, 410);
    }
}

/**
 * 422 Unprocessable Entity
 */
class UnprocessableEntityException extends HttpException
{
    protected $errors = [];

    public function __construct($message = "Unprocessable Entity", array $errors = [])
    {
        $this->errors = $errors;
        parent::__construct($message, 422);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}

/**
 * 429 Too Many Requests
 */
class TooManyRequestsException extends HttpException
{
    public function __construct($message = "Too Many Requests", $retryAfter = null)
    {
        $headers = [];
        if ($retryAfter !== null) {
            $headers['Retry-After'] = $retryAfter;
        }
        
        parent::__construct($message, 429, $headers);
    }
}

/**
 * 500 Internal Server Error
 */
class InternalServerErrorException extends HttpException
{
    public function __construct($message = "Internal Server Error")
    {
        parent::__construct($message, 500);
    }
}

/**
 * 501 Not Implemented
 */
class NotImplementedException extends HttpException
{
    public function __construct($message = "Not Implemented")
    {
        parent::__construct($message, 501);
    }
}

/**
 * 502 Bad Gateway
 */
class BadGatewayException extends HttpException
{
    public function __construct($message = "Bad Gateway")
    {
        parent::__construct($message, 502);
    }
}

/**
 * 503 Service Unavailable
 */
class ServiceUnavailableException extends HttpException
{
    public function __construct($message = "Service Unavailable", $retryAfter = null)
    {
        $headers = [];
        if ($retryAfter !== null) {
            $headers['Retry-After'] = $retryAfter;
        }
        
        parent::__construct($message, 503, $headers);
    }
}

/**
 * 504 Gateway Timeout
 */
class GatewayTimeoutException extends HttpException
{
    public function __construct($message = "Gateway Timeout")
    {
        parent::__construct($message, 504);
    }
}