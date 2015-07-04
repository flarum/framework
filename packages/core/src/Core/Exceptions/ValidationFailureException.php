<?php namespace Flarum\Core\Exceptions;

use Exception;
use Illuminate\Support\MessageBag;
use DomainException;

class ValidationFailureException extends DomainException
{
    /**
     * @var MessageBag
     */
    protected $errors;

    /**
     * @var array
     */
    protected $input = array();

    /**
     * @param string $message
     * @param int $code
     * @param Exception $previous
     */
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->errors = new MessageBag;
    }

    /**
     * @param MessageBag $errors
     * @return $this
     */
    public function setErrors(MessageBag $errors)
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * @return MessageBag
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param array $input
     * @return $this
     */
    public function setInput(array $input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @return array
     */
    public function getInput()
    {
        return $this->input;
    }
}
