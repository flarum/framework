<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Queue\QueueFactory;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Worker;
use Psr\Log\LoggerInterface;
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
        }

        if ($this->needsUpdate()) {
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
        $this->pauseQueueForUpdate();

        $pipe = new MiddlewarePipe;
        $pipe->pipe(new BasePath($this->basePath()));
        $pipe->pipe(
            new HttpMiddleware\ResolveRoute($this->container->make('flarum.update.routes'))
        );
        $pipe->pipe(new HttpMiddleware\ExecuteRoute());

        return $pipe;
    }

    /**
     * Pause the queue for as long as the site needs updating.
     *
     * The window between new code landing and its migrations running is when a
     * worker is most dangerous — it reserves jobs against a schema the code no
     * longer matches. That window opens the moment `settings.version` drifts,
     * long before an admin visits /update, so the pause is armed here, the
     * moment the app first routes a request to the updater, rather than waiting
     * for the migration itself.
     *
     * `MigrateCommand` clears this same flag when the migrations finish, so a
     * pause armed here is lifted there. Setting an already-set flag is a
     * no-op, so re-arming on every request through this window is harmless.
     *
     * Mirrors `queue:pause --all`, including its refusal to act when pausing is
     * disabled — a flag no worker reads is only misleading state.
     */
    protected function pauseQueueForUpdate(): void
    {
        if (! Worker::$pausable) {
            return;
        }

        try {
            $connection = $this->container->make(Queue::class)->getConnectionName();

            $this->container->make(QueueFactory::class)->pause($connection, '*');
        } catch (\Throwable $e) {
            // The updater must render even if the queue cannot be reached — a
            // failure to pause must never be a failure to update. The worker's
            // own maintenance handling remains the backstop.
            $this->container->make(LoggerInterface::class)->warning(
                'Could not pause the queue before serving the updater: '.$e->getMessage()
            );
        }
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
