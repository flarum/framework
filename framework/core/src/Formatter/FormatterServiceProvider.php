<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Formatter;

use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

class FormatterServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot(Dispatcher $events)
    {
        $events->listen(Enabled::class, [$this, 'flushFormatter']);
        $events->listen(Disabled::class, [$this, 'flushFormatter']);
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('flarum.formatter', function (Container $container) {
            return new Formatter(
                $container->make('cache.store'),
                $container->make('events'),
                $this->app->storagePath().'/formatter'
            );
        });

        $this->app->alias('flarum.formatter', Formatter::class);
    }

    public function flushFormatter()
    {
        $this->app->make('flarum.formatter')->flush();
    }
}
