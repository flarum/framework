<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Laminas\Stratigility\Middleware\OriginalMessages;
use Laminas\Stratigility\MiddlewarePipe;
use Middlewares\BasePath;
use Middlewares\BasePathRouter;
use Middlewares\RequestHandler;
use Psr\Http\Server\RequestHandlerInterface;

class InstalledApp implements AppInterface
{
    public function __construct(
        protected Container $container,
        protected Config $config
    ) {
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getRequestHandler(): RequestHandlerInterface
    {
        if ($this->config->inHighMaintenanceMode()) {
            return $this->container->make('flarum.maintenance.handler');
        } elseif ($this->needsUpdate()) {
            return $this->getUpdaterHandler();
        }

        $pipe = new MiddlewarePipe;

        $pipe->pipe(new HttpMiddleware\ProcessIp());
        $pipe->pipe(new BasePath($this->basePath()));
        $pipe->pipe(new OriginalMessages);
        $pipe->pipe(
            new BasePathRouter([
                $this->subPath('api') => 'flarum.api.handler',
                $this->subPath('admin') => 'flarum.admin.handler',
                '/' => 'flarum.forum.handler',
            ])
        );
        $pipe->pipe(new RequestHandler($this->container));

        return $pipe;
    }

    protected function needsUpdate(): bool
    {
        $settings = $this->container->make(SettingsRepositoryInterface::class);
        $version = $settings->get('version');

        return $version !== Application::VERSION;
    }

    protected function getUpdaterHandler(): RequestHandlerInterface|MiddlewarePipe
    {
        $pipe = new MiddlewarePipe;
        $pipe->pipe(new BasePath($this->basePath()));
        $pipe->pipe(
            new HttpMiddleware\ResolveRoute($this->container->make('flarum.update.routes'))
        );
        $pipe->pipe(new HttpMiddleware\ExecuteRoute());

        return $pipe;
    }

    protected function basePath(): string
    {
        return $this->config->url()->getPath() ?: '/';
    }

    protected function subPath(string $pathName): string
    {
        return '/'.($this->config['paths'][$pathName] ?? $pathName);
    }

    public function getConsoleCommands(): array
    {
        return array_map(function ($command) {
            $command = $this->container->make($command);

            if ($command instanceof Command) {
                $command->setLaravel($this->container);
            }

            return $command;
        }, $this->container->make('flarum.console.commands'));
    }
}
