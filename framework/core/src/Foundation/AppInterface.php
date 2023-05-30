<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Console\Command\Command;

interface AppInterface
{
    public function getRequestHandler(): RequestHandlerInterface;

    /**
     * @return Command[]
     */
    public function getConsoleCommands(): array;
}
