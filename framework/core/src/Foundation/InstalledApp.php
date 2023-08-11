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

    public function getMiddlewareStack(): array
    {
//        if ($this->config->inMaintenanceMode()) {
//            return $this->container->make('flarum.maintenance.handler');
//        }

        return match ($this->needsUpdate()) {
            true => $this->getUpdaterMiddlewareStack(),
            false => $this->getStandardMiddlewareStack(),
        };
    }

    protected function needsUpdate(): bool
    {
        $settings = $this->container->make(SettingsRepositoryInterface::class);
        $version = $settings->get('version');

        return $version !== Application::VERSION;
    }

    protected function getUpdaterMiddlewareStack(): array
    {
        return [
            new BasePath($this->basePath()),
        ];
    }

    protected function getStandardMiddlewareStack(): array
    {
        return [
            new BasePath($this->basePath()),
            new OriginalMessages,
        ];
    }

    protected function basePath(): string
    {
        return $this->config->url()->getPath() ?: '/';
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
