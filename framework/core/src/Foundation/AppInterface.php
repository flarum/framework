<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Illuminate\Contracts\Container\Container;
use Symfony\Component\Console\Command\Command;

interface AppInterface
{
    public function getContainer(): Container;

    public function getMiddlewareStack(): array;

    /**
     * @return Command[]
     */
    public function getConsoleCommands(): array;
}
