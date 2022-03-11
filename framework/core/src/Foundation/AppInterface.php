<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

interface AppInterface
{
    /**
     * @return \Psr\Http\Server\RequestHandlerInterface
     */
    public function getRequestHandler();

    /**
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getConsoleCommands();
}
