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

class MissingDependenciesExceptionHandler
{
    public function handle(MissingDependenciesException $e): HandledError
    {
        return (new HandledError(
            $e,
            'missing_dependencies',
            409
        ))->withDetails($this->errorDetails($e));
    }

    protected function errorDetails(MissingDependenciesException $e): array
    {
        return [
            [
                'extension' => $e->extension->getTitle(),
                'extensions' => ExtensionManager::pluckTitles($e->missing_dependencies),
            ]
        ];
    }
}
