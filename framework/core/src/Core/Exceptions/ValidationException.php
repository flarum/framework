<?php namespace Flarum\Core\Exceptions;

use Exception;

class ValidationException extends Exception
{
    protected $messages;

    public function __construct(array $messages)
    {
        $this->messages = $messages;
    }

    public function getMessages()
    {
        return $this->messages;
    }
}
