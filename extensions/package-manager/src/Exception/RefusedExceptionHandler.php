<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Exception;

use Flarum\Foundation\ErrorHandling\HandledError;

class RefusedExceptionHandler
{
    public function handle(RefusedException $e): HandledError
    {
        return (new HandledError($e, $e->getType(), $e->getStatus()))
            ->withDetails([['detail' => $e->getMessage()]]);
    }
}
