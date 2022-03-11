<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Exception;

use Exception;
use Flarum\Foundation\KnownError;

class InvalidConfirmationTokenException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'invalid_confirmation_token';
    }
}
