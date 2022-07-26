<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Exception;

class IOException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'io_error';
    }
}
