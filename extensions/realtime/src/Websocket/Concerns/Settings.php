<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Concerns;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;

trait Settings
{
    protected function flarumConfig(): array
    {
        /** @var \Flarum\Foundation\Config $config */
        $config = resolve('flarum.config');

        return (array) $config;
    }

    protected function flarumSettings(): ?SettingsRepositoryInterface
    {
        if (resolve(Container::class)->bound(SettingsRepositoryInterface::class)) {
            return resolve(SettingsRepositoryInterface::class);
        }

        return null;
    }
}
