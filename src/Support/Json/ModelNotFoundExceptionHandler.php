<?php
namespace Flarum\Support\Json;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ModelNotFoundExceptionHandler implements ExceptionHandler
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
        return $e instanceof ModelNotFoundException;
    }

    /**
     * Handle the provided exception.
     *
     * @param Exception $e
     * @return mixed
     */
    public function handle(Exception $e)
    {
        return new ResponseBag(404, []);
    }
}
