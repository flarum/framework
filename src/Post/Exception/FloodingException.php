<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Exception;

use Exception;
use Flarum\Foundation\KnownError;

class FloodingException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'too_many_requests';
    }
}
