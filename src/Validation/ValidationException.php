<?php

namespace Velocix\Validation;

class ValidationException extends \Exception
{
    protected $errors;

    public function __construct($message, $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function errors()
    {
        return $this->errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}