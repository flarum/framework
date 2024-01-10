<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Exception;

use Exception;
use Flarum\Foundation\KnownError;

class ExtensionNotInstalledException extends Exception implements KnownError
{
    public function __construct(string $extensionId)
    {
        parent::__construct("Extension {$extensionId} is not installed.");
    }

    public function getType(): string
    {
        return 'extension_not_installed';
    }
}
