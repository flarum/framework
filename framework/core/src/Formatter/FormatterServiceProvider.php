<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Formatter;

use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Config;
use Flarum\Foundation\Paths;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

class FormatterServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('flarum.formatter', function (Container $container) {
            return new Formatter(
                new Repository($container->make('cache.filestore')),
                $container[Paths::class]->storage.'/formatter',
                $container->make(Config::class)
            );
        });

        $this->container->alias('flarum.formatter', Formatter::class);
    }

    public function boot(Container $container, Dispatcher $events): void
    {
        // Extensions can configure the formatter differently depending on
        // which other extensions are enabled, so toggling any extension can
        // change it, not just one that registers formatter extenders.
        $events->listen([Enabled::class, Disabled::class], function () use ($container) {
            $container->make(Formatter::class)->flush();
        });
    }
}
