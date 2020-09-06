<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension\Exception;

use Exception;
use Flarum\Extension\Extension;

class MissingDependenciesException extends Exception
{
    public $extension;
    public $missing_dependencies;

    /**
     * @param $extension: The extension we are attempting to activate.
     * @param $missing_dependencies: Extension IDs of the missing flarum extension dependencies for this extension
     */
    public function __construct(Extension $extension, array $missing_dependencies = null)
    {
        $this->extension = $extension;
        $this->missing_dependencies = $missing_dependencies;

        parent::__construct(implode("\n", $missing_dependencies));
    }
}
