<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai;

use RuntimeException;

class NotConfiguredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No AI connection is configured. Add one in the admin panel, or under `ai` in config.php.');
    }
}
