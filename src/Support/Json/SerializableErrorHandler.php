<?php
namespace Flarum\Support\Json;

use Exception;
use Flarum\Core\Exceptions\JsonApiSerializable;

class SerializableErrorHandler implements ExceptionHandler
{
    /**
     * If the exception handler is able to format a response for the provided exception,
     * then the implementation should return true.
     *
     * @param Exception $e
     * @return boolean
     */
    public function manages(Exception $e)
    {
        return $e instanceof JsonApiSerializable;
    }

    /**
     * Handle the provided exception.
     *
     * @param Exception $e
     * @return mixed
     */
    public function handle(Exception $e)
    {
        return new ResponseBag($e->getStatusCode(), $e->getErrors());
    }
}
