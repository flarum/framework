<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension\Exception;

use Exception;
use Flarum\Extension\ExtensionManager;

class CircularDependenciesException extends Exception
{
    public function __construct(
        public array $circular_dependencies
    ) {
        parent::__construct('Circular dependencies detected: '.implode(', ', ExtensionManager::pluckTitles($this->circular_dependencies)).' - aborting. Please fix this by disabling the extensions that are causing the circular dependencies.');
    }
}
