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

class ExtensionIncompatibleWithFlarumException extends Exception implements KnownError
{
    public function __construct(string $package, string $constraint)
    {
        parent::__construct("Extension $package requires flarum/core $constraint which is incompatible with the installed version.");
    }

    public function getType(): string
    {
        return 'extension_incompatible_with_instance';
    }
}
