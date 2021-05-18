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
    /**
     * @var Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get a cache store instance by name.
     *
     * @param  string|null $name
     * @return Repository
     */
    public function store($name = null)
    {
        return $this->container['cache.store'];
    }
}
