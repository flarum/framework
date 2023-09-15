<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

class InstalledApp implements AppInterface
{
    public function __construct(
        protected ApplicationContract $app,
    ) {
    }

    public function getContainer(): ApplicationContract
    {
        return $this->app;
    }

    public function getMiddlewareStack(): array
    {
//        if ($this->config->inMaintenanceMode()) {
//            return $this->app->make('flarum.maintenance.handler');
//        }

        return match ($this->needsUpdate()) {
            true => $this->getUpdaterMiddlewareStack(),
            false => $this->getStandardMiddlewareStack(),
        };
    }

    protected function needsUpdate(): bool
    {
        $settings = $this->app->make(SettingsRepositoryInterface::class);
        $version = $settings->get('version');

        return $version !== Application::VERSION;
    }

    protected function getUpdaterMiddlewareStack(): array
    {
        return [
            //
        ];
    }

    protected function getStandardMiddlewareStack(): array
    {
        return [
            //
        ];
    }

    public function getConsoleCommands(): array
    {
        return array_map(function ($command) {
            $command = $this->app->make($command);

            if ($command instanceof Command) {
                $command->setLaravel($this->app);
            }

            return $command;
        }, $this->app->make('flarum.console.commands'));
    }
}
