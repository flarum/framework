<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Event;

class LoggingIn
{
    public $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }
}
