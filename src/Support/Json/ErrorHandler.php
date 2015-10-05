<?php
namespace Flarum\Support\Json;

use Exception;

class ErrorHandler
{
    /**
     * Stores the valid handlers.
     *
     * @var array
     */
    private $handlers = [];

    /**
     * Handle the exception provided.
     *
     * @param Exception $exception
     * @return mixed
     * @throws InvalidHandlerException
     */
    public function handle(Exception $exception)
    {
        foreach ($this->handlers as $handler) {
            if ($handler->manages($exception)) {
                return $handler->handle($exception);
            }
        }

        throw new InvalidHandlerException;
    }

    /**
     * Register a new exception handler.
     *
     * @param ExceptionHandler $handler
     */
    public function registerHandler(ExceptionHandler $handler)
    {
        $this->handlers[] = $handler;
    }
}
