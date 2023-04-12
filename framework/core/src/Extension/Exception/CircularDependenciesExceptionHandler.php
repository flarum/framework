<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension\Exception;

use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\ErrorHandling\HandledError;

class CircularDependenciesExceptionHandler
{
    public function handle(CircularDependenciesException $e): HandledError
    {
        return (new HandledError(
            $e,
            'circular_dependencies',
            409
        ))->withDetails($this->errorDetails($e));
    }

    protected function errorDetails(CircularDependenciesException $e): array
    {
        return [[
            'extensions' => ExtensionManager::pluckTitles($e->circular_dependencies),
        ]];
    }
}
