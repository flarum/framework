<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue\Driver;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Queue;

interface DriverInterface
{
    public function build(): Queue;

    public function setContainer(Container $container): self;
}
