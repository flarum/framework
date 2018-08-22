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
use Flarum\Foundation\Application;
use Flarum\Http\Middleware\DispatchRoute;
use Flarum\Http\Middleware\HandleErrorsWithWhoops;
use Flarum\Http\Middleware\StartSession;
use Flarum\Install\Console\InstallCommand;
use Zend\Stratigility\MiddlewarePipe;

class Installer implements AppInterface
{
    /**
     * @var Application
     */
    protected $laravel;

    public function __construct(Application $laravel)
    {
        $this->laravel = $laravel;
    }

    /**
     * @return \Psr\Http\Server\RequestHandlerInterface
     */
    public function getRequestHandler()
    {
        $pipe = new MiddlewarePipe;
        $pipe->pipe($this->laravel->make(HandleErrorsWithWhoops::class));
        $pipe->pipe($this->laravel->make(StartSession::class));
        $pipe->pipe(
            $this->laravel->make(
                DispatchRoute::class,
                ['routes' => $this->laravel->make('flarum.install.routes')]
            )
        );

        return $pipe;
    }

    /**
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getConsoleCommands()
    {
        return [
            $this->laravel->make(InstallCommand::class),
        ];
    }
}
