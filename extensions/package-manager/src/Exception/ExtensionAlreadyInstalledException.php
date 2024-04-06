<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Exception;

use Exception;
use Flarum\Extension\Extension;
use Flarum\Foundation\KnownError;

class ExtensionAlreadyInstalledException extends Exception implements KnownError
{
    public function __construct(Extension $extension)
    {
        parent::__construct("Extension {$extension->getTitle()} is already installed.");
    }

    public function getType(): string
    {
        return 'extension_already_installed';
    }
}
