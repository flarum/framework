<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Announcements;

use Flarum\Announcements\Console\RefreshAnnouncementsCommand;
use Flarum\Announcements\Console\WeeklySchedule;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Config;
use Illuminate\Contracts\Container\Container;

class AnnouncementsServiceProvider extends AbstractServiceProvider
{
    public function boot(Container $container, Config $config): void
    {
        if ($config['flarum_announcements.disabled'] ?? false) {
            return;
        }

        $container->extend('flarum.console.commands', function (array $commands) {
            $commands[] = RefreshAnnouncementsCommand::class;

            return $commands;
        });

        $container->extend('flarum.console.scheduled', function (array $scheduled) {
            $scheduled[] = [
                'command' => RefreshAnnouncementsCommand::class,
                'args' => [],
                'callback' => new WeeklySchedule(),
            ];

            return $scheduled;
        });
    }
}
