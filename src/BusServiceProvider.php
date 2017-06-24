<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum;

use Flarum\Foundation\AbstractServiceProvider;

/**
 * @deprecated
 */
class BusServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->app->make('Illuminate\Contracts\Bus\Dispatcher')->mapUsing(function ($command) {
            return get_class($command).'Handler@handle';
        });
    }
}
