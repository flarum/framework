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
 * This exception is thrown when someone attempts to disable an extension
 * that other enabled extensions depend on.
 */
class DependentExtensionsException extends Exception
{
    /**
     * @param $extension: The extension we are attempting to disable.
     * @param $dependent_extensions: Enabled Flarum extensions that depend on this extension.
     */
    public function __construct(
        public Extension $extension,
        public array $dependent_extensions
    ) {
        parent::__construct($extension->getTitle().' could not be disabled, because it is a dependency of: '.implode(', ', ExtensionManager::pluckTitles($dependent_extensions)));
    }
}
