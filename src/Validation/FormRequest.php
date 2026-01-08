<?php

namespace Velocix\Validation;

use Velocix\Http\Request;

abstract class FormRequest
{
    protected $request;
    protected $validator;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    abstract public function rules();

    public function messages()
    {
        return [];
    }

    public function authorize()
    {
        return true;
    }

    public function validate()
    {
        if (!$this->authorize()) {
            throw new \Exception('Unauthorized', 403);
        }

        $this->validator = Validator::make(
            $this->request->all(),
            $this->rules(),
            $this->messages()
        );

        if ($this->validator->fails()) {
            throw new ValidationException('Validation failed', $this->validator->errors());
        }

        return $this->validator->validated();
    }

    public function validated()
    {
        return $this->validate();
    }
}