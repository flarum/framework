<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install;

use Flarum\Foundation\AppInterface;
use Flarum\Http\Middleware\DispatchRoute;
use Flarum\Http\Middleware\HandleErrorsWithWhoops;
use Flarum\Http\Middleware\StartSession;
use Flarum\Install\Console\InstallCommand;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Validation\Factory;
use Zend\Stratigility\MiddlewarePipe;

class Installer implements AppInterface
{
    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return \Psr\Http\Server\RequestHandlerInterface
     */
    public function getRequestHandler()
    {
        $pipe = new MiddlewarePipe;
        $pipe->pipe($this->container->make(HandleErrorsWithWhoops::class));
        $pipe->pipe($this->container->make(StartSession::class));
        $pipe->pipe(
            new DispatchRoute($this->container->make('flarum.install.routes'))
        );

        return $pipe;
    }

    /**
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getConsoleCommands()
    {
        return [
            new InstallCommand(
                $this->container->make(Installation::class),
                new Factory($this->container->make(Translator::class))
            ),
        ];
    }
}
