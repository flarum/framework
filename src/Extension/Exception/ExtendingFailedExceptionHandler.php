<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension\Exception;

use Flarum\Foundation\ErrorHandling\HandledError;

class ExtendingFailedExceptionHandler
{
    public function handle(ExtendingFailedException $e): HandledError
    {
        return (new HandledError(
            $e,
            'extending_failed',
            500
        ))->withDetails($this->errorDetails($e));
    }

    protected function errorDetails(ExtendingFailedException $e): array
    {
        return [
            [
                'extension' => $e->extension->getTitle(),
                'extender' => get_class($e->extender)
            ]
        ];
    }
}
