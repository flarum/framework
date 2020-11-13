<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Filter\Filterer;
use Flarum\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

class Filter implements ExtenderInterface
{
    private $resource;
    private $filters = [];
    private $filterMutators = [];

    /**
     * @param string $resource: The ::class attribute of the resource this applies to, which is typically an Eloquent model.
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Add a filter to run when the resource is filtered.
     *
     * @param string $filterClass: The ::class attribute of the filter you are adding.
     */
    public function addFilter(string $filterClass)
    {
        $this->filters[] = $filterClass;

        return $this;
    }

    /**
     * Add a callback through which to run all filter queries after filters have been applied.
     */
    public function addFilterMutator($callback)
    {
        $this->filterMutators[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        foreach ($this->filters as $filter) {
            Filterer::addFilter($this->resource, $container->make($filter));
        }

        foreach ($this->filterMutators as $mutator) {
            Filterer::addFilterMutator($this->resource, ContainerUtil::wrapCallback($mutator, $container));
        }
    }
}
