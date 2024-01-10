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

class IndirectExtensionDependencyCannotBeRemovedException extends Exception implements KnownError
{
    public function __construct(string $extensionId)
    {
        parent::__construct("Extension {$extensionId} cannot be directly removed because it is a dependency of another extension.");
    }

    public function getType(): string
    {
        return 'extension_not_directly_dependency';
    }
}
