<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Console\Cache;

use Illuminate\Contracts\Cache\Factory as FactoryContract;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Container\Container;

class Factory implements FactoryContract
{
    public function __construct(
        protected Container $container
    ) {
    }

    public function store($name = null): Repository
    {
        return $this->container['cache.store'];
    }
}
