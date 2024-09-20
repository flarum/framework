<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling\ExceptionHandler;

use Flarum\Foundation\ErrorHandling\HandledError;
use Illuminate\Database\QueryException;

class QueryExceptionHandler
{
    public function handle(QueryException $e): HandledError
    {
        return (new HandledError(
            $e,
            'db_error',
            500,
            true
        ))->withDetails([]);
    }
}
