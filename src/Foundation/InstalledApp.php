<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Foundation\Console\InfoCommand;
use Flarum\Http\Middleware\DispatchRoute;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Laminas\Stratigility\Middleware\OriginalMessages;
use Laminas\Stratigility\MiddlewarePipe;
use Middlewares\BasePath;
use Middlewares\BasePathRouter;
use Middlewares\RequestHandler;

class InstalledApp implements AppInterface
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $config;

    public function __construct(Container $container, array $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return \Psr\Http\Server\RequestHandlerInterface
     */
    public function getRequestHandler()
    {
        if ($this->inMaintenanceMode()) {
            return new MaintenanceModeHandler();
        } elseif ($this->needsUpdate()) {
            return $this->getUpdaterHandler();
        }

        $pipe = new MiddlewarePipe;

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

    private function inMaintenanceMode(): bool
    {
        return $this->config['offline'] ?? false;
    }

    private function needsUpdate(): bool
    {
        $settings = $this->container->make(SettingsRepositoryInterface::class);
        $version = $settings->get('version');

        return $version !== Application::VERSION;
    }

    /**
     * @return \Psr\Http\Server\RequestHandlerInterface
     */
    private function getUpdaterHandler()
    {
        $pipe = new MiddlewarePipe;
        $pipe->pipe(new BasePath($this->basePath()));
        $pipe->pipe(
            new DispatchRoute($this->container->make('flarum.update.routes'))
        );

        return $pipe;
    }

    private function basePath(): string
    {
        return parse_url($this->config['url'], PHP_URL_PATH) ?: '/';
    }

    private function subPath($pathName): string
    {
        return '/'.($this->config['paths'][$pathName] ?? $pathName);
    }

    /**
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getConsoleCommands()
    {
        $commands = [];

        // The info command is a special case, as it requires a config parameter that's only available here.
        $commands[] = $this->container->make(InfoCommand::class, ['config' => $this->config]);

        foreach ($this->container->make('flarum.console.commands') as $command) {
            $newCommand = $this->container->make($command);

            if ($newCommand instanceof Command) {
                $newCommand->setLaravel($this->container);
            }

            $commands[] = $newCommand;
        }

        return $commands;
    }
}
