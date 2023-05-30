<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install;

use Flarum\Foundation\AppInterface;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Foundation\ErrorHandling\WhoopsFormatter;
use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Install\Console\InstallCommand;
use Illuminate\Contracts\Container\Container;
use Laminas\Stratigility\MiddlewarePipe;
use Psr\Http\Server\RequestHandlerInterface;

class Installer implements AppInterface
{
    public function __construct(
        protected Container $container
    ) {
    }

    public function getRequestHandler(): RequestHandlerInterface
    {
        $pipe = new MiddlewarePipe;
        $pipe->pipe(new HttpMiddleware\HandleErrors(
            $this->container->make(Registry::class),
            $this->container->make(WhoopsFormatter::class),
            $this->container->tagged(Reporter::class)
        ));
        $pipe->pipe($this->container->make(HttpMiddleware\StartSession::class));
        $pipe->pipe(
            new HttpMiddleware\ResolveRoute($this->container->make('flarum.install.routes'))
        );
        $pipe->pipe(new HttpMiddleware\ExecuteRoute());

        return $pipe;
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
