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
use Middlewares\BasePathRouter;
use Middlewares\RequestHandler;
use Zend\Stratigility\MiddlewarePipe;

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
            return new MaintenanceModeHandler();
        } elseif ($this->needsUpdate()) {
            return $this->getUpdaterHandler();
        }

        $pipe = new MiddlewarePipe;

        $pipe->pipe(
            new BasePathRouter([
                $this->subPath('api') => 'flarum.api.middleware',
                $this->subPath('admin') => 'flarum.admin.middleware',
                $this->subPath('') => 'flarum.forum.middleware',
            ])
        );
        $pipe->pipe(new RequestHandler($this->laravel));

        return $pipe;
    }

    private function inMaintenanceMode(): bool
    {
        return $this->config['offline'] ?? false;
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

    private function subPath($pathName): string
    {
        return parse_url($this->laravel->url($pathName), PHP_URL_PATH) ?: '/';
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
