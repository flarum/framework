<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Database\Console\GenerateMigrationCommand;
use Flarum\Database\Console\MigrateCommand;
use Flarum\Database\Console\ResetCommand;
use Flarum\Foundation\Console\CacheClearCommand;
use Flarum\Foundation\Console\InfoCommand;
use Flarum\Http\Middleware\DispatchRoute;
use Flarum\Settings\SettingsRepositoryInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Stratigility\MiddlewarePipe;
use function Zend\Stratigility\middleware;
use function Zend\Stratigility\path;

class InstalledApp implements AppInterface
{
    /**
     * @var Application
     */
    protected $laravel;

    /**
     * @var array
     */
    protected $config;

    public function __construct(Application $laravel, array $config)
    {
        $this->laravel = $laravel;
        $this->config = $config;
    }

    /**
     * @return \Psr\Http\Server\RequestHandlerInterface
     */
    public function getRequestHandler()
    {
        if ($this->inMaintenanceMode()) {
            return $this->getMaintenanceHandler();
        } elseif ($this->needsUpdate()) {
            return $this->getUpdaterHandler();
        }

        $pipe = new MiddlewarePipe;

        $pipe->pipe($this->subPath('api', 'flarum.api.middleware'));
        $pipe->pipe($this->subPath('admin', 'flarum.admin.middleware'));
        $pipe->pipe($this->subPath('', 'flarum.forum.middleware'));

        return $pipe;
    }

    private function inMaintenanceMode(): bool
    {
        return $this->config['offline'] ?? false;
    }

    /**
     * @return \Psr\Http\Server\RequestHandlerInterface
     */
    private function getMaintenanceHandler()
    {
        $pipe = new MiddlewarePipe;

        $pipe->pipe(middleware(function () {
            // FIXME: Fix path to 503.html
            // TODO: FOR API render JSON-API error document for HTTP 503
            return new HtmlResponse(
                file_get_contents(__DIR__.'/../../503.html'), 503
            );
        }));

        return $pipe;
    }

    private function needsUpdate(): bool
    {
        $settings = $this->laravel->make(SettingsRepositoryInterface::class);
        $version = $settings->get('version');

        return $version !== Application::VERSION;
    }

    /**
     * @return \Psr\Http\Server\RequestHandlerInterface
     */
    public function getUpdaterHandler()
    {
        $pipe = new MiddlewarePipe;
        $pipe->pipe(
            new DispatchRoute($this->laravel->make('flarum.update.routes'))
        );

        return $pipe;
    }

    private function subPath($pathName, $middlewareStack)
    {
        return path(
            parse_url($this->laravel->url($pathName), PHP_URL_PATH) ?: '/',
            $this->laravel->make($middlewareStack)
        );
    }

    /**
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getConsoleCommands()
    {
        return [
            $this->laravel->make(GenerateMigrationCommand::class),
            $this->laravel->make(InfoCommand::class, ['config' => $this->config]),
            $this->laravel->make(MigrateCommand::class),
            $this->laravel->make(ResetCommand::class),
            $this->laravel->make(CacheClearCommand::class),
        ];
    }
}
