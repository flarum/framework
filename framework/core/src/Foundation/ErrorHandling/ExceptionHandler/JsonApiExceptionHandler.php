<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling\ExceptionHandler;

use Flarum\Foundation\ErrorHandling\HandledError;
use Throwable;
use Tobyz\JsonApiServer\Exception\ErrorProvider;

class JsonApiExceptionHandler
{
    public function handle(ErrorProvider&Throwable $e): HandledError
    {
        return (new HandledError(
            $e,
            'validation_error',
            intval($e->getJsonApiStatus())
        ))->withDetails($e->getJsonApiErrors());
    }
}
