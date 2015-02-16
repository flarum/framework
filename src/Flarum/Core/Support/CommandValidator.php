<?php namespace Flarum\Core\Support;

use Illuminate\Validation\Factory;
use Flarum\Core\Support\Exceptions\ValidationFailureException;
use Event;

class CommandValidator
{
    protected $rules = [];

    protected $validator;

    public function __construct(Factory $validator)
    {
        $this->validator = $validator;
    }

    public function validate($command)
    {
        if (empty($command->user)) {
            throw new InvalidArgumentException('Empty argument [user] in command ['.get_class($command).']');
        }

        $validator = $this->validator->make(get_object_vars($command), $this->rules);

        $this->fireValidationEvent([$validator, $command]);

        if ($validator->fails()) {
            $this->throwValidationException($validator->errors(), $validator->getData());
        }
    }

    protected function fireValidationEvent(array $arguments)
    {
        Event::fire(str_replace('\\', '.', get_class($this)), $arguments);
    }

    protected function throwValidationException($errors, $input)
    {
        $exception = new ValidationFailureException;
        $exception->setErrors($errors)->setInput($input);
        throw $exception;
    }
}
