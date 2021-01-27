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
use Flarum\Extension\ExtensionManager;

/**
 * This exception is thrown when someone attempts to enable an extension
 * whose Flarum extension dependencies are not all enabled.
 */
class MissingDependenciesException extends Exception
{
    public $extension;
    public $missing_dependencies;

    /**
     * @param $extension: The extension we are attempting to enable.
     * @param $missing_dependencies: Extensions that this extension depends on, and are not enabled.
     */
    public function __construct(Extension $extension, array $missing_dependencies = null)
    {
        $this->extension = $extension;
        $this->missing_dependencies = $missing_dependencies;

        parent::__construct($extension->getTitle().' could not be enabled, because it depends on: '.implode(', ', ExtensionManager::pluckTitles($missing_dependencies)));
    }
}
