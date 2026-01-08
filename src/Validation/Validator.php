<?php

namespace Velocix\Validation;

class Validator
{
    protected $data = [];
    protected $rules = [];
    protected $errors = [];
    protected $customMessages = [];

    public function __construct($data, $rules, $customMessages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
    }

    public static function make($data, $rules, $customMessages = [])
    {
        return new static($data, $rules, $customMessages);
    }

    public function validate()
    {
        foreach ($this->rules as $field => $rules) {
            $ruleList = is_string($rules) ? explode('|', $rules) : $rules;
            
            foreach ($ruleList as $rule) {
                $this->validateRule($field, $rule);
            }
        }

        return empty($this->errors);
    }

    public function fails()
    {
        return !$this->validate();
    }

    public function validated()
    {
        if ($this->fails()) {
            throw new ValidationException('Validation failed', $this->errors);
        }

        $validated = [];
        foreach ($this->rules as $field => $rules) {
            if (isset($this->data[$field])) {
                $validated[$field] = $this->data[$field];
            }
        }

        return $validated;
    }

    public function errors()
    {
        return $this->errors;
    }

    protected function validateRule($field, $rule)
    {
        if (strpos($rule, ':') !== false) {
            list($ruleName, $parameter) = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $parameter = null;
        }

        $method = 'validate' . ucfirst($ruleName);

        if (!method_exists($this, $method)) {
            throw new \Exception("Validation rule '{$ruleName}' does not exist");
        }

        $value = $this->data[$field] ?? null;

        if (!$this->$method($field, $value, $parameter)) {
            $this->addError($field, $ruleName, $parameter);
        }
    }

    protected function addError($field, $rule, $parameter = null)
    {
        $message = $this->getMessage($field, $rule, $parameter);
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }

    protected function getMessage($field, $rule, $parameter = null)
    {
        $key = "{$field}.{$rule}";
        
        if (isset($this->customMessages[$key])) {
            return $this->customMessages[$key];
        }

        $messages = [
            'required' => "The {$field} field is required.",
            'email' => "The {$field} must be a valid email address.",
            'min' => "The {$field} must be at least {$parameter} characters.",
            'max' => "The {$field} must not exceed {$parameter} characters.",
            'numeric' => "The {$field} must be a number.",
            'integer' => "The {$field} must be an integer.",
            'alpha' => "The {$field} may only contain letters.",
            'alphanumeric' => "The {$field} may only contain letters and numbers.",
            'url' => "The {$field} must be a valid URL.",
            'confirmed' => "The {$field} confirmation does not match.",
            'unique' => "The {$field} has already been taken.",
            'exists' => "The selected {$field} is invalid.",
            'in' => "The selected {$field} is invalid.",
            'between' => "The {$field} must be between {$parameter}.",
            'date' => "The {$field} must be a valid date.",
            'regex' => "The {$field} format is invalid.",
            'same' => "The {$field} and {$parameter} must match.",
        ];

        return $messages[$rule] ?? "The {$field} field is invalid.";
    }

    protected function validateRequired($field, $value, $parameter)
    {
        if (is_null($value)) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if (is_array($value) && count($value) === 0) {
            return false;
        }

        return true;
    }

    protected function validateEmail($field, $value, $parameter)
    {
        if (is_null($value)) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateMin($field, $value, $parameter)
    {
        if (is_null($value)) {
            return true;
        }

        return strlen($value) >= (int)$parameter;
    }

    protected function validateMax($field, $value, $parameter)
    {
        if (is_null($value)) {
            return true;
        }

        return strlen($value) <= (int)$parameter;
    }

    protected function validateNumeric($field, $value, $parameter)
    {
        if (is_null($value)) {
            return true;
        }

        return is_numeric($value);
    }

    protected function validateInteger($field, $value, $parameter)
    {
        if (is_null($value)) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    protected function validateAlpha($field, $value, $parameter)
    {
        if (is_null($value)) {
            return true;
        }

        return preg_match('/^[a-zA-Z]+$/', $value);
    }

    protected function validateAlphanumeric($field, $value, $parameter)
    {
        if (is_null($value)) {
            return true;
        }

        return preg_match('/^[a-zA-Z0-9]+$/', $value);
    }

    protected function validateUrl($field, $value, $parameter)
    {
        if (is_null($value)) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    protected function validateConfirmed($field, $value, $parameter)
    {
        $confirmField = $field . '_confirmation';
        return isset($this->data[$confirmField]) && $value === $this->data[$confirmField];
    }

    protected function validateUnique($field, $value, $parameter)
    {
        if (is_null($value)) {
            return true;
        }

        $params = explode(',', $parameter);
        $table = $params[0];
        $column = $params[1] ?? $field;
        $except = $params[2] ?? null;
        $exceptColumn = $params[3] ?? 'id';

        $query = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
        $bindings = [$value];

        if ($except) {
            $query .= " AND {$exceptColumn} != ?";
            $bindings[] = $except;
        }

        $connection = \Velocix\Database\Connection::make(config('database'));
        $stmt = $connection->query($query, $bindings);
        $result = $stmt->fetch();

        return $result['count'] == 0;
    }

    protected function validateExists($field, $value, $parameter)
    {
        if (is_null($value)) {
            return true;
        }

        $params = explode(',', $parameter);
        $table = $params[0];
        $column = $params[1] ?? $field;

        $query = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
        
        $connection = \Velocix\Database\Connection::make(config('database'));
        $stmt = $connection->query($query, [$value]);
        $result = $stmt->fetch();

        return $result['count'] > 0;
    }

    protected function validateIn($field, $value, $parameter)
    {
        if (is_null($value)) {
            return true;
        }

        $values = explode(',', $parameter);
        return in_array($value, $values);
    }

    protected function validateBetween($field, $value, $parameter)
    {
        if (is_null($value)) {
            return true;
        }

        list($min, $max) = explode(',', $parameter);
        $length = strlen($value);

        return $length >= (int)$min && $length <= (int)$max;
    }

    protected function validateDate($field, $value, $parameter)
    {
        if (is_null($value)) {
            return true;
        }

        return strtotime($value) !== false;
    }

    protected function validateRegex($field, $value, $parameter)
    {
        if (is_null($value)) {
            return true;
        }

        return preg_match($parameter, $value);
    }

    protected function validateSame($field, $value, $parameter)
    {
        return isset($this->data[$parameter]) && $value === $this->data[$parameter];
    }
}