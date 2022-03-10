<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling\ExceptionHandler;

use Flarum\Foundation\ErrorHandling\HandledError;
use Illuminate\Validation\ValidationException;

class IlluminateValidationExceptionHandler
{
    public function handle(ValidationException $e): HandledError
    {
        return (new HandledError(
            $e,
            'validation_error',
            422
        ))->withDetails($this->errorDetails($e));
    }

    protected function errorDetails(ValidationException $e): array
    {
        $errors = $e->errors();

        return array_map(function ($field, $messages) {
            return [
                'detail' => implode("\n", $messages),
                'source' => ['pointer' => "/data/attributes/$field"]
            ];
        }, array_keys($errors), $errors);
    }
}
