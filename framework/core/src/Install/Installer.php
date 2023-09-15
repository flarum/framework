<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install;

use Flarum\Foundation\AppInterface;
use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Install\Console\InstallCommand;
use Illuminate\Contracts\Foundation\Application;

class Installer implements AppInterface
{
    public function __construct(
        protected Application $container
    ) {
    }

    public function getContainer(): Application
    {
        return $this->container;
    }

    public function getMiddlewareStack(): array
    {
        return [
            $this->container->make(HttpMiddleware\StartSession::class),
        ];
    }

    /**
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getConsoleCommands(): array
    {
        return [
            new InstallCommand(
                $this->container->make(Installation::class)
            ),
        ];
    }
}
