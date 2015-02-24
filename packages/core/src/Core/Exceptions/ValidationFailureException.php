<?php namespace Flarum\Core\Exceptions;

use Illuminate\Support\MessageBag;

class ValidationFailureException extends \InvalidArgumentException
{
    protected $errors;

    protected $input = array();

    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = new MessageBag;
    }

    public function setErrors(MessageBag $errors)
    {
        $this->errors = $errors;

        return $this;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function setInput(array $input)
    {
        $this->input = $input;

        return $this;
    }

    public function getInput()
    {
        return $this->input;
    }
}
