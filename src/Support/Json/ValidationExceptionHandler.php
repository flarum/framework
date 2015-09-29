<?php
namespace Flarum\Support\Json;

use Exception;
use Illuminate\Contracts\Validation\ValidationException;

class ValidationExceptionHandler implements ExceptionHandler
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
        return $e instanceof ValidationException;
    }

    /**
     * Handle the provided exception.
     *
     * @param Exception $e
     * @return mixed
     */
    public function handle(Exception $e)
    {
        $status = 422;
        $errors = $this->formatErrors($e->errors()->toArray());

        return new ResponseBag($status, $errors);
    }

    /**
     * Format the errors as required for output.
     *
     * @param array $errors
     * @return array
     */
    private function formatErrors(array $errors)
    {
        return array_map(function ($field, $messages) {
            return [
                'detail' => implode("\n", $messages),
                'source' => ['pointer' => '/data/attributes/' . $field],
            ];
        }, array_keys($errors), $errors);
    }
}
