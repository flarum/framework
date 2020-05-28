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
use Flarum\Foundation\KnownError;

class MissingDependenciesException extends Exception implements KnownError
{
    public $extension;
    public $flarum_dependencies;

    /**
     * @param $extension: The extension we are attempting to activate.
     * @param $flarum_dependencies: Extension IDs of the missing flarum extension dependencies for this extension
     */
    public function __construct(Extension $extension, array $flarum_dependencies = [])
    {
        $this->extension = $extension;
        $this->$flarum_dependencies = $flarum_dependencies;
    }

    public function getType(): string
    {
        return 'missing_dependencies';
    }
}
