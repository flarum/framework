<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

class Container extends \Illuminate\Container\Container
{
    public function terminating(): void
    {
    }

    public function terminate(): void
    {
    }
}
