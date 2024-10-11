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

class CannotFetchExternalExtension extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'cannot_fetch_external_extension';
    }
}
